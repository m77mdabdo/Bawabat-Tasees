<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasEmailAuthentication, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * is_active and roles are never mass-assignable: they change only through the
     * staff screen (UserResource), which guards against locking everyone out.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
    ];

    /** Mirrors the column defaults so a new instance is complete before it is saved. */
    protected $attributes = [
        'is_active' => true,
        'locale' => 'ar',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** Only active staff whose role grants dashboard access may enter (deny by default). */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->can('dashboard.access');
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SuperAdmin->value);
    }

    public function roleEnum(): ?Role
    {
        return Role::tryFrom((string) $this->roles->first()?->name);
    }

    /** The dashboard language this person chose (ar or en). */
    public function dashboardLocale(): string
    {
        return in_array($this->locale, ['ar', 'en'], true) ? $this->locale : 'ar';
    }

    /** Every sign-in is finished with a one-time code emailed to this address; it cannot be switched off. */
    public function hasEmailAuthentication(): bool
    {
        return true;
    }

    public function toggleEmailAuthentication(bool $condition): void {}
}
