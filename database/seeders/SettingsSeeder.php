<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'contact_phone', 'group' => 'contact', 'value' => '+966 5xx xxx xxx (placeholder — update in Settings)'],
            ['key' => 'contact_whatsapp', 'group' => 'contact', 'value' => '+966 5xx xxx xxx (placeholder — update in Settings)'],
            ['key' => 'contact_email', 'group' => 'contact', 'value' => 'info@example.com (placeholder — update in Settings)'],
            ['key' => 'contact_address', 'group' => 'contact', 'value' => 'Riyadh, Saudi Arabia (placeholder — update in Settings)'],

            ['key' => 'social_facebook', 'group' => 'social', 'value' => 'https://facebook.com/yourpage (placeholder — update in Settings)'],
            ['key' => 'social_instagram', 'group' => 'social', 'value' => 'https://instagram.com/yourpage (placeholder — update in Settings)'],
            ['key' => 'social_linkedin', 'group' => 'social', 'value' => 'https://linkedin.com/company/yourpage (placeholder — update in Settings)'],
            ['key' => 'social_twitter', 'group' => 'social', 'value' => 'https://x.com/yourpage (placeholder — update in Settings)'],

            ['key' => 'default_meta_title', 'group' => 'seo_defaults', 'value' => 'Bawabat Taasees Al Sharikat (placeholder — update in Settings)'],
            ['key' => 'default_meta_description', 'group' => 'seo_defaults', 'value' => 'Company formation and investment services in Saudi Arabia (placeholder — update in Settings)'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                ]
            );
        }
    }
}
