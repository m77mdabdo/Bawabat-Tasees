<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * FABRICATED demo data — for local development and demos only.
 *
 * Deliberately NOT called by DatabaseSeeder. Everything below is invented:
 *
 *   - TestimonialSeeder      client quotes nobody actually said
 *   - CampaignSeeder         campaigns with made-up budget/spend figures
 *   - ConversionEventSeeder  sample leads and the revenue attached to them
 *
 * Shipping any of it would put fake client quotes on the public site and
 * make the Reports screen compute ROI against money that was never spent
 * — so `php artisan migrate --seed` on production gives you real content
 * and nothing else.
 *
 * Load it explicitly in dev:
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * Order matters: campaigns first, because ConversionEventSeeder's sample
 * leads carry external campaign ids that must resolve to existing
 * campaign records.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn(
            'Seeding FABRICATED demo data (testimonials, campaigns, leads, conversions). '
            .'Never run this against production.'
        );

        $this->call([
            TestimonialSeeder::class,
            CampaignSeeder::class,
            ConversionEventSeeder::class,
        ]);
    }
}
