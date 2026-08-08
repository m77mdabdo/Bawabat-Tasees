<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Campaign extends Model
{
    /**
     * Channels an admin can pick from. A fixed vocabulary rather than a
     * lookup table, matching ConversionEvent::TYPES — reporting groups on
     * these values.
     */
    public const PLATFORMS = [
        'google',
        'meta',
        'tiktok',
        'snapchat',
        'linkedin',
        'x',
        'other',
    ];

    protected $fillable = [
        'name',
        'platform',
        'external_campaign_id',
        'budget',
        'spend',
        'currency',
        'starts_on',
        'ends_on',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'budget' => 'decimal:2',
            'spend' => 'decimal:2',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Leads resolved to this campaign via leads.linked_campaign_id — NOT
     * via leads.campaign_id, which holds the raw external ad-platform
     * string (see the 2026_08_08_160000 migration for why the two are
     * separate).
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'linked_campaign_id');
    }

    /**
     * Every conversion logged against any of this campaign's leads, so a
     * campaign's revenue is one query rather than a loop over its leads.
     */
    public function conversionEvents(): HasManyThrough
    {
        return $this->hasManyThrough(
            ConversionEvent::class,
            Lead::class,
            'linked_campaign_id',
            'lead_id',
            'id',
            'id'
        );
    }
}
