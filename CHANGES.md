# CHANGES.md

## Code Review Notes

On beginning, as soon as I opened the project, the VsCode was getting errors from not existing specific models but first on reading the project on git, I noticed a hint on EnsureCampaignIsDraft file. But let's take it to steps.

### Missing models

`Contact`, `ContactList` and `CampaignSend` are referenced throughout the codebase but the model files didn't exist. Created all three with the appropriate fillable fields and relationships.

### EnsureCampaignIsDraft middleware — logic is inverted

The condition was checking `=== 'draft'` and but if is to ensure the campaign is draft I have to change it to `!== 'draft'`.

### Campaign stats loading full collection into memory

`getStatsAttribute` was calling `$this->sends` which loads every send record into PHP and then counting with `->where()` on the collection. For large campaigns this will cause memory issues. Replaced with a single grouped DB query.

### scheduled_at stored as string

The column was defined as `string` in the migration. Date comparisons in the scheduler won't work reliably with string columns. Created a new migration and changing to `timestamp` and added the `datetime` cast on the model.

### Scheduler re-dispatches already sent campaigns

The query had no status filter so every minute it would pick up campaigns that were already sent and dispatch them again. Added `->where('status', 'draft')` to the query.

### CampaignService has no guard against double dispatch

Nothing was stopping dispatch from being called twice on the same campaign. Added a status check at the top and moved the `status = sending` update to before the sends are created so concurrent calls can't both get through.

### SendCampaignEmail job has no retry limit

No `$tries` defined means Laravel retries forever by default. Added `$tries = 3` and a `failed()` method to mark the send as failed in the database when all retries are exhausted.

### contacts.email has no unique constraint

Duplicate emails could be inserted, meaning the same person would receive multiple copies of a campaign. Created a new migration and added `->unique()` to the migration.

### contact_contact_list pivot has no unique constraint

Same contact could be added to the same list more than once. Like I said previously, created a new migration and added a unique constraint on `(contact_id, contact_list_id)`.