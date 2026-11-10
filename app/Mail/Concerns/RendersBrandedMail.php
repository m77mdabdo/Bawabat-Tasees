<?php

namespace App\Mail\Concerns;

use Illuminate\Mail\Mailables\Content;

/**
 * Every email shares the same frame (mail/layout) and needs the same four variables. A mailable says
 * which view and which language; this fills in the frame's title, kicker and preheader.
 */
trait RendersBrandedMail
{
    /** @param  array<string, mixed>  $with */
    protected function branded(string $view, string $locale, string $title, string $kicker, array $with = []): Content
    {
        return new Content(view: $view, with: [
            'locale' => $locale,
            'title' => $title,
            'kicker' => $kicker,
            'preheader' => $with['preheader'] ?? $title,
            ...$with,
        ]);
    }

    protected static function supportedLocale(?string $locale): string
    {
        return in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
    }
}
