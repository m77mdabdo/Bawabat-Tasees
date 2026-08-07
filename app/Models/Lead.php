<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'whatsapp_number',
        'email',
        'nationality',
        'country_of_residence',
        'requested_service_id',
        'requested_activity',
        'owns_external_company',
        'message',
        'type',
        'source_platform',
        'campaign_name',
        'campaign_id',
        'adset_name',
        'adset_id',
        'ad_name',
        'ad_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'landing_page_url',
        'referrer_url',
        'gclid',
        'fbclid',
        'ttclid',
        'first_touch',
        'latest_touch',
        'consent_given',
        'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'owns_external_company' => 'boolean',
            'first_touch' => 'array',
            'latest_touch' => 'array',
            'consent_given' => 'boolean',
            'consented_at' => 'datetime',
        ];
    }

    /**
     * withTrashed() because a lead is a historical record: soft-deleting a
     * service must not erase which service an existing lead asked about.
     * Without it the relationship resolves to null and the dashboard shows
     * a blank service on every affected lead.
     */
    public function requestedService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'requested_service_id')->withTrashed();
    }

    public function conversionEvents(): HasMany
    {
        return $this->hasMany(ConversionEvent::class);
    }
}
