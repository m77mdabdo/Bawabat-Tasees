<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class LeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['key' => 'facebook', 'label' => 'Facebook'],
            ['key' => 'instagram', 'label' => 'Instagram'],
            ['key' => 'google', 'label' => 'Google'],
            ['key' => 'tiktok', 'label' => 'TikTok'],
            ['key' => 'snapchat', 'label' => 'Snapchat'],
            ['key' => 'linkedin', 'label' => 'LinkedIn'],
            ['key' => 'whatsapp', 'label' => 'WhatsApp'],
            ['key' => 'organic', 'label' => 'Organic'],
            ['key' => 'referral', 'label' => 'Referral'],
            ['key' => 'direct', 'label' => 'Direct'],
            ['key' => 'other', 'label' => 'Other'],
        ];

        foreach ($sources as $sortOrder => $source) {
            LeadSource::updateOrCreate(
                ['key' => $source['key']],
                [
                    'label' => $source['label'],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }
}
