<?php

namespace App\Leads;

use App\Models\Lead;
use App\Models\NotificationRecipient;
use App\Services\Cms\SettingsRepository;
use Illuminate\Support\Collection;

/**
 * Answers "who should be emailed about this enquiry?".
 *
 * The dashboard-managed list wins. Until someone fills it, the address from config or the
 * site's public contact email is used, so a new installation never drops an enquiry silently.
 */
final class RecipientDirectory
{
    public function __construct(private readonly SettingsRepository $settings) {}

    /** @return Collection<int, NotificationRecipient> unsaved models for fallbacks */
    public function forLead(Lead $lead): Collection
    {
        $recipients = NotificationRecipient::query()->for($lead->type)->orderBy('id')->get();

        if ($recipients->isNotEmpty() || NotificationRecipient::query()->active()->exists()) {
            return $recipients;
        }

        $fallback = config('notifications.fallback_address') ?: $this->settings->string('contact_email');

        return filter_var($fallback, FILTER_VALIDATE_EMAIL)
            ? collect([new NotificationRecipient(['email' => $fallback])])
            : collect();
    }
}
