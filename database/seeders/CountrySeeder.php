<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

/**
 * Investor-origin markets shown on "الدول التي نخدمها". Slugs are explicit
 * so re-running matches existing rows rather than creating duplicates.
 */
class CountrySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->countries() as $sortOrder => $country) {
            Country::updateOrCreate(
                ['slug' => $country['slug']],
                [
                    'name' => $country['name'],
                    'notes' => $country['notes'],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }

    private function countries(): array
    {
        return [
            [
                'slug' => 'united-arab-emirates',
                'name' => ['ar' => 'الإمارات العربية المتحدة', 'en' => 'United Arab Emirates'],
                'notes' => [
                    'ar' => 'نخدم عدداً كبيراً من الشركات الإماراتية الراغبة في التوسع داخل السوق السعودي.',
                    'en' => 'We serve a large number of UAE-based companies expanding into the Saudi market.',
                ],
            ],
            [
                'slug' => 'kuwait',
                'name' => ['ar' => 'الكويت', 'en' => 'Kuwait'],
                'notes' => [
                    'ar' => 'إجراءات ميسّرة للمستثمرين الخليجيين مع معاملة مماثلة للمواطن السعودي في معظم الأنشطة.',
                    'en' => 'Streamlined procedures for GCC investors, with treatment comparable to Saudi nationals across most activities.',
                ],
            ],
            [
                'slug' => 'egypt',
                'name' => ['ar' => 'مصر', 'en' => 'Egypt'],
                'notes' => [
                    'ar' => 'من أكثر الأسواق نشاطاً في تأسيس الشركات الخدمية والمقاولات داخل المملكة.',
                    'en' => 'One of the most active markets for establishing services and contracting companies in the Kingdom.',
                ],
            ],
            [
                'slug' => 'jordan',
                'name' => ['ar' => 'الأردن', 'en' => 'Jordan'],
                'notes' => [
                    'ar' => 'خبرة واسعة في مرافقة الشركات الأردنية في قطاعات التقنية والاستشارات.',
                    'en' => 'Extensive experience supporting Jordanian companies in the technology and consulting sectors.',
                ],
            ],
            [
                'slug' => 'india',
                'name' => ['ar' => 'الهند', 'en' => 'India'],
                'notes' => [
                    'ar' => 'نساعد الشركات الهندية على استيفاء متطلبات التصديق والترجمة المعتمدة للمستندات.',
                    'en' => 'We help Indian companies meet the attestation and certified translation requirements for their documents.',
                ],
            ],
            [
                'slug' => 'united-kingdom',
                'name' => ['ar' => 'المملكة المتحدة', 'en' => 'United Kingdom'],
                'notes' => [
                    'ar' => 'مرافقة كاملة للشركات البريطانية من ترخيص الاستثمار وحتى بدء التشغيل.',
                    'en' => 'End-to-end support for British companies, from the investment licence through to operational launch.',
                ],
            ],
            [
                'slug' => 'united-states',
                'name' => ['ar' => 'الولايات المتحدة', 'en' => 'United States'],
                'notes' => [
                    'ar' => 'خبرة في هيكلة الفروع والمقار الإقليمية للشركات الأمريكية.',
                    'en' => 'Experience structuring branches and regional headquarters for US companies.',
                ],
            ],
            [
                'slug' => 'china',
                'name' => ['ar' => 'الصين', 'en' => 'China'],
                'notes' => [
                    'ar' => 'دعم متخصص في قطاعات الصناعة والتوريد واللوجستيات.',
                    'en' => 'Specialised support across the manufacturing, supply and logistics sectors.',
                ],
            ],
        ];
    }
}
