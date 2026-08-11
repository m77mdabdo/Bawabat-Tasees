<?php

namespace App\Services\Cms;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for which settings exist, what type each is and
 * how it is validated — the editor UI, the Form Request and the seeder
 * all read this definition, so they cannot drift apart.
 *
 * Writes iterate THIS definition, never the request keys, mirroring
 * TrackingSettingController: a crafted payload can therefore never create
 * an arbitrary settings row or write to one that is not listed here.
 *
 * NOTE ON STORAGE. settings.value is a json column cast to 'array', but
 * every row holds a JSON-encoded scalar (e.g. "+96650…"). That is
 * pre-existing and works — json_decode('"x"') gives back the string — so
 * plain values round-trip unchanged. Bilingual settings exploit the same
 * column properly, storing {"ar": "...", "en": "..."}.
 */
class SettingsService
{
    public const TYPE_TEXT = 'text';

    public const TYPE_TRANSLATED = 'translated';

    public const TYPE_EMAIL = 'email';

    public const TYPE_PHONE = 'phone';

    public const TYPE_URL = 'url';

    public const TYPE_IMAGE = 'image';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    /**
     * group => [key => type]. Order here is the order rendered in the UI.
     */
    public const DEFINITION = [
        'general' => [
            'site_name' => self::TYPE_TRANSLATED,
            'site_tagline' => self::TYPE_TRANSLATED,
            'site_logo' => self::TYPE_IMAGE,
        ],
        'contact' => [
            'contact_phone' => self::TYPE_PHONE,
            'contact_email' => self::TYPE_EMAIL,
            'contact_address' => self::TYPE_TRANSLATED,
        ],
        'whatsapp' => [
            'contact_whatsapp' => self::TYPE_PHONE,
        ],
        'social' => [
            'social_facebook' => self::TYPE_URL,
            'social_instagram' => self::TYPE_URL,
            'social_twitter' => self::TYPE_URL,
            'social_linkedin' => self::TYPE_URL,
            'social_youtube' => self::TYPE_URL,
            'social_tiktok' => self::TYPE_URL,
            'social_snapchat' => self::TYPE_URL,
        ],
        'seo_defaults' => [
            'default_meta_title' => self::TYPE_TEXT,
            'default_meta_description' => self::TYPE_TEXT,
        ],
        /*
         * The homepage is the one public page with no backing record, so it
         * had nothing to hang a SeoMeta row off and fell all the way through
         * to default_meta_title — which is how the site's single most
         * important <title> ended up reading "… (placeholder — update in
         * Settings)" in both locales.
         *
         * TRANSLATED, unlike the two defaults above: /  and /en are separate
         * pages to a crawler and deserve their own copy, whereas the
         * site-wide defaults are a last resort where one string is
         * acceptable.
         */
        'home_seo' => [
            'home_meta_title' => self::TYPE_TRANSLATED,
            'home_meta_description' => self::TYPE_TRANSLATED,
        ],
        /*
         * Visitor session journeys. Both of these are privacy controls, not
         * preferences, which is why they live in the admin UI rather than in
         * a config file: turning capture off and shortening retention are
         * things a site owner may need to do immediately, without a deploy.
         *
         * Retention is enforced by the visitor-sessions:prune command, which
         * clamps whatever is stored here before it reaches a DELETE.
         */
        'visitor_sessions' => [
            'visitor_sessions_enabled' => self::TYPE_BOOLEAN,
            'visitor_sessions_retention_days' => self::TYPE_INTEGER,
        ],
    ];

    public function __construct(
        private readonly SettingsRepository $settings,
        private readonly ContentPublishingService $contentPublishingService,
    ) {}

    /**
     * Every editable key, flattened.
     *
     * @return array<string, string> key => type
     */
    public static function keys(): array
    {
        return array_merge(...array_values(self::DEFINITION));
    }

    /**
     * Persists the submitted values.
     *
     * @param  array<string, mixed>  $values  validated `settings` payload
     */
    public function update(array $values, ?UploadedFile $logo = null): void
    {
        DB::transaction(function () use ($values, $logo) {
            foreach (self::DEFINITION as $group => $keys) {
                foreach ($keys as $key => $type) {
                    // Image settings are handled separately: their value
                    // comes from an upload, not the text payload, and an
                    // absent file must leave the current logo alone.
                    if ($type === self::TYPE_IMAGE) {
                        if ($logo && $key === 'site_logo') {
                            $this->writeImage($key, $group, $logo);
                        }

                        continue;
                    }

                    // An unchecked checkbox is absent from the payload
                    // entirely, so a boolean cannot use the guard below —
                    // "not submitted" IS the value false, whereas for every
                    // other type it means "leave alone".
                    if ($type === self::TYPE_BOOLEAN) {
                        $this->write($key, $group, (bool) ($values[$key] ?? false));

                        continue;
                    }

                    // array_key_exists, not isset: a deliberately cleared
                    // field arrives as null and must be stored as such.
                    if (! array_key_exists($key, $values)) {
                        continue;
                    }

                    $this->write($key, $group, $this->normalise($type, $values[$key]));
                }
            }
        });

        // The repository caches the table for the rest of the request, so
        // without this the redirect would re-render the OLD values.
        $this->settings->flush();
    }

    private function writeImage(string $key, string $group, UploadedFile $file): void
    {
        $previous = $this->settings->get($key);

        $path = $this->contentPublishingService->replaceImage(
            $file,
            'settings',
            is_string($previous) ? $previous : null
        );

        $this->write($key, $group, $path);
    }

    private function write(string $key, string $group, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    /**
     * Blank strings become null so "unset" and "empty" are the same state —
     * the footer and contact blocks all test for a truthy value before
     * rendering, so a stored "" would otherwise print an empty row.
     */
    private function normalise(string $type, mixed $value): mixed
    {
        if ($type === self::TYPE_INTEGER) {
            return is_numeric($value) ? (int) $value : null;
        }

        if ($type === self::TYPE_TRANSLATED) {
            $value = array_filter(
                array_map(fn ($v) => is_string($v) ? trim($v) : $v, (array) $value),
                fn ($v) => is_string($v) && $v !== ''
            );

            return $value === [] ? null : $value;
        }

        $value = is_string($value) ? trim($value) : $value;

        return $value === '' ? null : $value;
    }
}
