<?php

namespace Database\Seeders;

use App\Models\TrackingSetting;
use Illuminate\Database\Seeder;

class TrackingSettingSeeder extends Seeder
{
    public function run(): void
    {
        $keys = [
            'meta_pixel_id',
            'gtm_container_id',
            'ga4_measurement_id',
            'google_ads_conversion_id',
            'google_ads_conversion_label',
            'tiktok_pixel_id',
        ];

        foreach ($keys as $key) {
            TrackingSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => null,
                    'is_active' => false,
                ]
            );
        }
    }
}
