<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Seeder;

/**
 * SAMPLE campaigns so the Campaigns and Reports screens render real
 * structure on a fresh install rather than an empty state.
 *
 * These are NOT real campaigns and the budget/spend figures are invented.
 * Delete them before launch — Reports computes ROI from `spend`, so
 * leaving them in place makes the dashboard report a return on money that
 * was never spent.
 *
 * external_campaign_id values match what ConversionEventSeeder puts on its
 * sample lead, so the link resolves on a fresh seed.
 */
class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->campaigns() as $campaign) {
            Campaign::updateOrCreate(
                ['external_campaign_id' => $campaign['external_campaign_id']],
                $campaign + ['currency' => 'SAR', 'is_active' => true]
            );
        }
    }

    private function campaigns(): array
    {
        return [
            [
                'name' => 'حملة الربيع — بحث جوجل (بيانات عينة)',
                'platform' => 'google',
                'external_campaign_id' => 'sample_campaign',
                'budget' => 30000.00,
                'spend' => 12000.00,
                'starts_on' => now()->subMonths(2)->toDateString(),
                'ends_on' => now()->addMonth()->toDateString(),
                'notes' => 'بيانات عينة — احذفها قبل الإطلاق. (SAMPLE DATA)',
            ],
            [
                'name' => 'حملة ميتا — إعادة الاستهداف (بيانات عينة)',
                'platform' => 'meta',
                'external_campaign_id' => 'sample_meta_retargeting',
                'budget' => 15000.00,
                'spend' => 9000.00,
                'starts_on' => now()->subMonth()->toDateString(),
                'notes' => 'بيانات عينة — احذفها قبل الإطلاق. (SAMPLE DATA)',
            ],
        ];
    }
}
