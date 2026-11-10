<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** An email address that is told about new enquiries from the website (managed in the dashboard). */
class NotificationRecipient extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'receives_consultations', 'receives_contact_messages', 'is_active'];

    protected $attributes = [
        'receives_consultations' => true,
        'receives_contact_messages' => true,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'receives_consultations' => 'boolean',
            'receives_contact_messages' => 'boolean',
            'is_active' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Active recipients that want to hear about this kind of enquiry. */
    public function scopeFor(Builder $query, string $leadType): Builder
    {
        return $query->active()->where(
            $leadType === Lead::TYPE_CONTACT ? 'receives_contact_messages' : 'receives_consultations',
            true,
        );
    }

    public function label(): string
    {
        return $this->name ?: $this->email;
    }
}
