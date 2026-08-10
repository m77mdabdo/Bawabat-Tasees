<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds every key the Settings editor knows about, so a fresh install has
 * a complete, editable set rather than a half-populated form.
 *
 * Social links are seeded EMPTY on purpose. They used to hold strings like
 * "https://facebook.com/yourpage (placeholder — update in Settings)",
 * which were never rendered so nobody noticed — but now that the footer
 * renders them they would be visibly broken links, and they would fail the
 * editor's URL validation on the next save. Empty means "not configured",
 * and the footer hides them.
 *
 * The contact placeholders are kept: they are self-labelling, they are
 * genuinely displayed on the homepage and contact page, and the admin now
 * has a UI to replace them.
 *
 * Idempotent via updateOrCreate on `key`, and existing values are NOT
 * overwritten — re-seeding must never wipe real contact details an admin
 * has entered.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->settings() as $setting) {
            $existing = Setting::where('key', $setting['key'])->first();

            if ($existing) {
                // Only correct the group; leave whatever value is stored.
                $existing->update(['group' => $setting['group']]);

                continue;
            }

            Setting::create($setting);
        }
    }

    /**
     * @return array<int, array{key: string, group: string, value: mixed}>
     */
    private function settings(): array
    {
        $placeholder = ' (placeholder — update in Settings)';

        return [
            // General
            ['key' => 'site_name', 'group' => 'general', 'value' => [
                'ar' => 'بوابة تأسيس الشركات',
                'en' => 'Bawabat Taasees Al Sharikat',
            ]],
            ['key' => 'site_tagline', 'group' => 'general', 'value' => [
                'ar' => 'بوابتك الموثوقة للاستثمار في السعودية',
                'en' => 'Your trusted gateway to investing in Saudi Arabia',
            ]],
            ['key' => 'site_logo', 'group' => 'general', 'value' => null],

            // Contact — displayed publicly, so the placeholders stay
            // visible (and self-labelled) until an admin replaces them.
            ['key' => 'contact_phone', 'group' => 'contact', 'value' => '+966 5xx xxx xxx'.$placeholder],
            ['key' => 'contact_email', 'group' => 'contact', 'value' => 'info@example.com'.$placeholder],
            ['key' => 'contact_address', 'group' => 'contact', 'value' => [
                'ar' => 'الرياض، المملكة العربية السعودية'.$placeholder,
                'en' => 'Riyadh, Saudi Arabia'.$placeholder,
            ]],

            // WhatsApp (the floating button)
            ['key' => 'contact_whatsapp', 'group' => 'whatsapp', 'value' => '+966 5xx xxx xxx'.$placeholder],

            // Social — empty until configured; the footer hides blanks.
            ['key' => 'social_facebook', 'group' => 'social', 'value' => null],
            ['key' => 'social_instagram', 'group' => 'social', 'value' => null],
            ['key' => 'social_twitter', 'group' => 'social', 'value' => null],
            ['key' => 'social_linkedin', 'group' => 'social', 'value' => null],
            ['key' => 'social_youtube', 'group' => 'social', 'value' => null],
            ['key' => 'social_tiktok', 'group' => 'social', 'value' => null],
            ['key' => 'social_snapchat', 'group' => 'social', 'value' => null],

            // SEO defaults
            ['key' => 'default_meta_title', 'group' => 'seo_defaults', 'value' => 'Bawabat Taasees Al Sharikat'.$placeholder],
            ['key' => 'default_meta_description', 'group' => 'seo_defaults', 'value' => 'Company formation and investment services in Saudi Arabia'.$placeholder],

            // Homepage SEO. NOT placeholders: the homepage has no backing
            // record to carry a SeoMeta row, so without real values here its
            // <title> falls through to default_meta_title above — which is
            // exactly how the placeholder ended up on the most-shared page on
            // the site. These are the real brand strings, so a fresh install
            // is correct before anyone opens the Settings screen.
            ['key' => 'home_meta_title', 'group' => 'home_seo', 'value' => [
                'ar' => 'بوابة تأسيس الشركات — تأسيس شركتك في السعودية',
                'en' => 'Bawabat Taasees Al Sharikat — Company Formation in Saudi Arabia',
            ]],
            ['key' => 'home_meta_description', 'group' => 'home_seo', 'value' => [
                'ar' => 'خدمات متكاملة لتأسيس الشركات والاستثمار في المملكة العربية السعودية: التراخيص، السجل التجاري، والمتطلبات النظامية للمستثمرين الأجانب.',
                'en' => 'End-to-end company formation and investment services in Saudi Arabia: licensing, commercial registration and the regulatory requirements for foreign investors.',
            ]],
        ];
    }
}
