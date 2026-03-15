<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\CampaignSend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    // Contacts

    public function test_can_list_contacts(): void
    {
        Contact::factory(20)->create();

        $this->getJson('/api/contacts')
             ->assertOk()
             ->assertJsonStructure([
                 'data' => [['id', 'name', 'email', 'status']],
                 'current_page',
                 'total',
             ]);
    }

    public function test_can_create_contact(): void
    {
        $this->postJson('/api/contacts', [
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ])->assertCreated();

        $this->assertDatabaseHas('contacts', ['email' => 'john@example.com']);
    }

    public function test_cannot_create_contact_with_duplicate_email(): void
    {
        Contact::factory()->create(['email' => 'john@example.com']);

        $this->postJson('/api/contacts', [
            'name'  => 'Another John',
            'email' => 'john@example.com',
        ])->assertUnprocessable()
          ->assertJsonValidationErrors(['email']);
    }

    // Contact Lists

    public function test_can_create_contact_list(): void
    {
        $this->postJson('/api/contact-lists', ['name' => 'Newsletter'])
             ->assertCreated();

        $this->assertDatabaseHas('contact_lists', ['name' => 'Newsletter']);
    }

    public function test_can_add_contact_to_list(): void
    {
        $list    = ContactList::factory()->create();
        $contact = Contact::factory()->create();

        $this->postJson("/api/contact-lists/{$list->id}/contacts", [
            'contact_id' => $contact->id,
        ])->assertOk();

        $this->assertDatabaseHas('contact_contact_list', [
            'contact_list_id' => $list->id,
            'contact_id'      => $contact->id,
        ]);
    }

    // Campaigns

    public function test_can_create_campaign(): void
    {
        $list = ContactList::factory()->create();

        $this->postJson('/api/campaigns', [
            'subject'         => 'Welcome Email',
            'body'            => 'Hello there!',
            'contact_list_id' => $list->id,
        ])->assertCreated();

        $this->assertDatabaseHas('campaigns', ['subject' => 'Welcome Email']);
    }

    public function test_can_show_campaign_with_stats(): void
    {
        $list     = ContactList::factory()->create();
        $campaign = Campaign::factory()->create(['contact_list_id' => $list->id]);

        CampaignSend::factory()->create(['campaign_id' => $campaign->id, 'status' => 'sent']);
        CampaignSend::factory()->create(['campaign_id' => $campaign->id, 'status' => 'pending']);

        $this->getJson("/api/campaigns/{$campaign->id}")
             ->assertOk()
             ->assertJsonStructure(['id', 'subject', 'stats' => ['pending', 'sent', 'failed', 'total']])
             ->assertJsonPath('stats.sent', 1)
             ->assertJsonPath('stats.pending', 1);
    }

    public function test_can_dispatch_draft_campaign(): void
    {
        $list    = ContactList::factory()->create();
        $contact = Contact::factory()->create(['status' => 'active']);
        $list->contacts()->attach($contact->id);

        $campaign = Campaign::factory()->create([
            'contact_list_id' => $list->id,
            'status'          => 'draft',
        ]);

        $this->postJson("/api/campaigns/{$campaign->id}/dispatch")
             ->assertOk();

        $this->assertDatabaseHas('campaigns', ['id' => $campaign->id, 'status' => 'sending']);
        $this->assertDatabaseHas('campaign_sends', ['campaign_id' => $campaign->id]);
    }

    public function test_cannot_dispatch_non_draft_campaign(): void
    {
        $list     = ContactList::factory()->create();
        $campaign = Campaign::factory()->create([
            'contact_list_id' => $list->id,
            'status'          => 'sending',
        ]);

        $this->postJson("/api/campaigns/{$campaign->id}/dispatch")
             ->assertUnprocessable();
    }

    public function test_dispatch_only_sends_to_active_contacts(): void
    {
        $list            = ContactList::factory()->create();
        $activeContact   = Contact::factory()->create(['status' => 'active']);
        $inactiveContact = Contact::factory()->create(['status' => 'unsubscribed']);

        $list->contacts()->attach([$activeContact->id, $inactiveContact->id]);

        $campaign = Campaign::factory()->create([
            'contact_list_id' => $list->id,
            'status'          => 'draft',
        ]);

        $this->postJson("/api/campaigns/{$campaign->id}/dispatch")->assertOk();

        $this->assertDatabaseCount('campaign_sends', 1);
        $this->assertDatabaseMissing('campaign_sends', ['contact_id' => $inactiveContact->id]);
    }
}
