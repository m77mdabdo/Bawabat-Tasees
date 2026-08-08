<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // PRODUCTION seeders only. Everything here is real, shippable
        // content — nothing invented. Sample testimonials, campaigns,
        // leads and conversion events live in DemoDataSeeder, which this
        // deliberately does NOT call: they would otherwise put fabricated
        // client quotes on the public site and make the dashboard report
        // revenue that was never earned.
        //
        //   php artisan db:seed --class=DemoDataSeeder
        //
        // See docs/setup-instructions.md.
        $this->call([
            AdminUserSeeder::class,
            LeadSourceSeeder::class,
            SettingsSeeder::class,
            TrackingSettingSeeder::class,
            PageContentSeeder::class,
            ServiceSeeder::class,
            CountrySeeder::class,
            FaqSeeder::class,
            ArticleSeeder::class,
            // Last of the content seeders: it derives its values from the
            // Pages and Articles the seeders above have just created.
            SeoMetaSeeder::class,
            // Reproduces the previously-hardcoded navbar as data, so the
            // public menu becomes admin-manageable without changing what
            // a visitor sees.
            MenuItemSeeder::class,
        ]);

        // The content seeders above author Arabic only — English comes
        // from this command's hand-written dictionary. Running it here
        // means `migrate:fresh --seed` alone produces a fully bilingual
        // site, instead of silently leaving every /en/ page falling back
        // to Arabic until someone remembers a second, separate step.
        //
        // Safe to run every time: the command skips any field that
        // already has English content (only --force overwrites), so
        // re-seeding an existing database is a no-op for content that
        // was already translated. Output is forwarded to the console
        // when seeding from the CLI, and buffered silently otherwise
        // (e.g. `$this->seed()` inside tests, where $command is null).
        Artisan::call('content:translate-to-english', [], $this->command?->getOutput());
    }
}
