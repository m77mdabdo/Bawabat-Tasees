<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionEvent extends Model
{
    use HasFactory;

    /**
     * The event types an admin can log from the dashboard. Kept here
     * rather than in a DB table because they are a fixed vocabulary the
     * reporting queries depend on, not admin-editable content.
     */
    public const TYPES = [
        'qualified',
        'meeting_booked',
        'contract_signed',
        'payment_received',
        'other',
    ];

    /**
     * Only these represent a completed sale — used by the "converted"
     * badge and filter so a lead that merely booked a meeting is not
     * counted as won.
     */
    public const WON_TYPES = [
        'contract_signed',
        'payment_received',
    ];

    protected $fillable = [
        'event_type',
        'value',
        'currency',
        'lead_id',
        'url',
        'utm_snapshot',
        'notes',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'utm_snapshot' => 'array',
            'occurred_at' => 'datetime',
            'value' => 'decimal:2',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * lead_id is nullOnDelete and Lead soft-deletes, so an event attached
     * to an archived lead keeps its foreign key — withTrashed() stops the
     * relation resolving to null on it, matching the Lead::requestedService
     * fix.
     */
    public function leadWithTrashed(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id')->withTrashed();
    }

    public function scopeWon(Builder $query): Builder
    {
        return $query->whereIn('event_type', self::WON_TYPES);
    }
}
