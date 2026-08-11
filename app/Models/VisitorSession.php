<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One visitor's journey through the public site.
 *
 * Complements attribution rather than duplicating it: `attribution` here is
 * a copy of the same touch cookies the forms submit, kept so a session reads
 * on its own, but `leads` remains the source of truth for where a lead came
 * from (see AttributionService).
 */
class VisitorSession extends Model
{
    use HasFactory;

    /**
     * Hard ceiling on the stored journey.
     *
     * `events` is a JSON column on a row that is updated on every single
     * page view, so it is the one part of this feature that can grow without
     * bound — a bot that slips the filter, or a genuinely long session,
     * would otherwise rewrite an ever-larger blob on every request. At 100
     * events a session is around 12KB, and 100 steps is far past the point
     * where a human reads a journey timeline.
     *
     * Past the cap, appends are DROPPED but `page_count` keeps counting, so
     * a truncated journey is still visibly a long one rather than silently
     * looking like a 100-page visit. The dashboard says so explicitly when
     * page_count exceeds the events it can show.
     */
    public const MAX_EVENTS = 100;

    public const TYPE_PAGEVIEW = 'pageview';

    public const TYPE_ACTION = 'action';

    /**
     * Deliberately narrow. Everything here is written by
     * VisitorSessionService from server-side request data, never from a
     * request payload — the only client-supplied values in the whole feature
     * are the beacon's duration numbers, and those are validated and clamped
     * before they reach this model.
     */
    protected $fillable = [
        'session_uid',
        'started_at',
        'last_activity_at',
        'entry_url',
        'referrer',
        'attribution',
        'device_type',
        'browser',
        'platform',
        'ip_prefix',
        'country',
        'events',
        'page_count',
        'total_duration_seconds',
        'lead_id',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'attribution' => 'array',
            'events' => 'array',
            'page_count' => 'integer',
            'total_duration_seconds' => 'integer',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->whereNotNull('lead_id');
    }

    public function scopeNotConverted(Builder $query): Builder
    {
        return $query->whereNull('lead_id');
    }

    public function hasConverted(): bool
    {
        return $this->lead_id !== null;
    }

    /**
     * True when the journey was longer than what is stored — see MAX_EVENTS.
     */
    public function isTruncated(): bool
    {
        return $this->page_count > count($this->events ?? []);
    }

    /**
     * The journey, oldest first, with anything malformed dropped.
     *
     * Defensive because `events` is a JSON blob: a partial write, a manual
     * edit or a future schema change should degrade to a shorter timeline,
     * not a 500 on the detail page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function journey(): array
    {
        return array_values(array_filter(
            $this->events ?? [],
            fn ($event) => is_array($event) && isset($event['type'], $event['ts'])
        ));
    }

    /**
     * The attribution source this session arrived on, for the index column.
     * Reads the latest touch, matching the rule AttributionService uses for
     * the flat lead columns: latest-touch is what immediately preceded the
     * visit.
     */
    public function sourcePlatform(): ?string
    {
        $source = $this->attribution['latest_touch']['utm_source']
            ?? $this->attribution['first_touch']['utm_source']
            ?? null;

        return is_string($source) && trim($source) !== '' ? $source : null;
    }

    public function campaignName(): ?string
    {
        $campaign = $this->attribution['latest_touch']['utm_campaign']
            ?? $this->attribution['first_touch']['utm_campaign']
            ?? null;

        return is_string($campaign) && trim($campaign) !== '' ? $campaign : null;
    }
}
