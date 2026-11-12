<?php

namespace App\Leads;

use App\Models\Lead;

/**
 * The rows an email shows for a lead, in one language. Kept apart from the mailables so the
 * team alert, the client confirmation and any future export read a lead the same way.
 */
final class LeadPresenter
{
    public static function kind(Lead $lead, string $locale): string
    {
        return __($lead->isContactMessage() ? 'mail.lead.kind_contact' : 'mail.lead.kind_consultation', [], $locale);
    }

    /** @return list<array{0: string, 1: string}> */
    public static function client(Lead $lead, string $locale): array
    {
        return self::rows($locale, [
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'whatsapp_number' => $lead->whatsapp_number,
            'nationality' => $lead->nationality,
            'country_of_residence' => $lead->country_of_residence,
        ]);
    }

    /** @return list<array{0: string, 1: string}> */
    public static function request(Lead $lead, string $locale): array
    {
        $service = $lead->requestedService?->getTranslation('name', $locale);

        return self::rows($locale, [
            'service' => $service,
            'requested_activity' => $lead->requested_activity,
            'owns_external_company' => $lead->isContactMessage() ? null
                : __($lead->owns_external_company ? 'mail.lead.yes' : 'mail.lead.no', [], $locale),
            'language' => __('mail.lead.languages.'.$lead->locale, [], $locale),
            'submitted_at' => $lead->created_at?->timezone('Asia/Riyadh')->locale($locale)->translatedFormat($locale === 'ar' ? 'j F Y، g:i a' : 'j F Y, g:i a'),
        ]);
    }

    /**
     * What the client's own confirmation repeats back. Only values the site chose (service,
     * language, time): the confirmation goes to whatever address was typed into a public form,
     * so it must never echo free text the sender wrote (requested activity, message).
     *
     * @return list<array{0: string, 1: string}>
     */
    public static function clientSummary(Lead $lead, string $locale): array
    {
        return self::rows($locale, [
            'service' => $lead->requestedService?->getTranslation('name', $locale),
            'language' => __('mail.lead.languages.'.$lead->locale, [], $locale),
            'submitted_at' => $lead->created_at?->timezone('Asia/Riyadh')->locale($locale)->translatedFormat($locale === 'ar' ? 'j F Y، g:i a' : 'j F Y, g:i a'),
        ]);
    }

    /**
     * The name used to greet the client: letters, spaces, apostrophes and hyphens only, no links,
     * addresses or domain-like words, at most 40 characters. Empty when nothing is left.
     */
    public static function greetingName(?string $name): string
    {
        $name = (string) $name;
        $name = preg_replace('~(?:[a-z][a-z0-9+.-]*://|www\.)\S*~iu', ' ', $name) ?? '';
        $name = preg_replace('~\S*@\S*~u', ' ', $name) ?? '';
        $name = preg_replace('~\S*[.。．]\p{L}{2,}\S*~u', ' ', $name) ?? '';
        $name = preg_replace("~[^\p{L}\p{M}\s'’-]~u", ' ', $name) ?? '';
        $name = trim(preg_replace('~\s+~u', ' ', $name) ?? '');

        return trim(mb_substr($name, 0, 40));
    }

    /** Marketing origin, only what is known. @return list<array{0: string, 1: string}> */
    public static function source(Lead $lead, string $locale): array
    {
        return self::rows($locale, [
            'source' => $lead->source_platform ?: $lead->utm_source,
            'campaign' => $lead->campaign_name ?: $lead->utm_campaign,
            'landing_page' => $lead->landing_page_url,
        ]);
    }

    /**
     * @param  array<string, ?string>  $values
     * @return list<array{0: string, 1: string}>
     */
    private static function rows(string $locale, array $values): array
    {
        $rows = [];
        foreach ($values as $field => $value) {
            if ($value !== null && trim((string) $value) !== '') {
                $rows[] = [__("mail.lead.fields.{$field}", [], $locale), (string) $value];
            }
        }

        return $rows;
    }
}
