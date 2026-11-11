<?php

namespace App\Enums;

/** Dashboard roles. Stored by name in spatie's roles table; what each may do is in App\Auth\PermissionMatrix. */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Manager = 'manager';
    case Editor = 'editor';
    case Sales = 'sales';
    case Viewer = 'viewer';

    /** English label; shown through __() so the Arabic dashboard reads it from lang/ar.json. */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Manager => 'Manager',
            self::Editor => 'Content Editor',
            self::Sales => 'Sales',
            self::Viewer => 'Viewer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Everything, including staff accounts and their roles.',
            self::Manager => 'Content, enquiries, marketing, reports and site settings. Not staff accounts.',
            self::Editor => 'Website content only: services, articles, pages, menu, media and comments.',
            self::Sales => 'Consultation requests and contact messages: view, follow up and record conversions.',
            self::Viewer => 'Read-only: reports, visitor journeys and enquiries.',
        };
    }

    /** @return array<string, string> value => translated label */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $r) => [$r->value => __($r->label())])->all();
    }
}
