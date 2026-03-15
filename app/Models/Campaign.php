<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ContactList;
use App\Models\CampaignSend;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = ['subject', 'body', 'contact_list_id', 'status', 'scheduled_at'];

    
    protected $casts = [
        'status' => 'string',
        'scheduled_at' => 'datetime',
    ];

    public function contactList(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ContactList::class);
    }

    public function sends(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CampaignSend::class);
    }

    public function getStatsAttribute(): array
    {
        $counts = $this->sends()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending' => (int) $counts->get('pending', 0),
            'sent'    => (int) $counts->get('sent', 0),
            'failed'  => (int) $counts->get('failed', 0),
            'total'   => (int) $counts->sum(),
        ];
    }
}
