<?php

namespace App\Support;

use App\Models\Lead;

/** Links from emails and alerts into the dashboard, in one place so a route change touches one file. */
final class DashboardLinks
{
    public static function lead(Lead $lead): string
    {
        return url('dashboard/leads/'.$lead->getKey());
    }

    public static function home(): string
    {
        return url('dashboard');
    }
}
