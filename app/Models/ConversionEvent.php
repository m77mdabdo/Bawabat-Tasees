<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionEvent extends Model
{
    protected $fillable = [
        'event_type',
        'lead_id',
        'url',
        'utm_snapshot',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'utm_snapshot' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
