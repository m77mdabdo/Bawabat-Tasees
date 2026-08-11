<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

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
        'linked_campaign_id',
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

    /**
     * Inbox read-state for type = 'contact' rows. Deliberately absent
     * from $fillable: it is never part of a public submission, and the
     * only writer is ContactMessageService.
     */
    public const TYPE_CONTACT = 'contact';

    /**
     * Mirrors the column default so a just-created Lead reports false
     * rather than null in the same request — without this, the inbox
     * badge and the handled/unhandled toggle both read null off a model
     * that has not been re-fetched.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_handled' => false,
    ];

    public function scopeContactMessages(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CONTACT);
    }

    public function scopeUnhandled(Builder $query): Builder
    {
        return $query->where('is_handled', false);
    }

    protected function casts(): array
    {
        return [
            'is_handled' => 'boolean',
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

    /**
     * The internal Campaign record this lead resolved to, if any.
     * Separate from the raw campaign_id string written by attribution —
     * see the 2026_08_08_160000 migration.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'linked_campaign_id');
    }

    /**
     * The visitor journeys that produced this lead.
     *
     * hasMany rather than hasOne even though one session produces at most
     * one lead: a visitor can convert twice from two separate sessions, and
     * a hasOne would quietly hide the second journey.
     */
    public function visitorSessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class);
    }

    /**
     * Newest first — the lead show page reads this as a timeline, and
     * ordering here keeps every caller consistent.
     */
    public function conversionEvents(): HasMany
    {
        return $this->hasMany(ConversionEvent::class)->orderByDesc('occurred_at');
    }

    /**
     * Only the event types that represent a completed sale, so a lead
     * that merely booked a meeting is not shown as won.
     */
    public function wonConversionEvents(): HasMany
    {
        return $this->conversionEvents()->whereIn('event_type', ConversionEvent::WON_TYPES);
    }

    public function scopeConverted(Builder $query): Builder
    {
        return $query->whereHas(
            'conversionEvents',
            fn (Builder $events) => $events->whereIn('event_type', ConversionEvent::WON_TYPES)
        );
    }

    public function scopeNotConverted(Builder $query): Builder
    {
        return $query->whereDoesntHave(
            'conversionEvents',
            fn (Builder $events) => $events->whereIn('event_type', ConversionEvent::WON_TYPES)
        );
    }

    public function isConverted(): bool
    {
        return $this->relationLoaded('wonConversionEvents')
            ? $this->wonConversionEvents->isNotEmpty()
            : $this->wonConversionEvents()->exists();
    }
}
