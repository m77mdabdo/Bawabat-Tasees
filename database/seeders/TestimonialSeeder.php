<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * FABRICATED testimonials — nobody said these. Loaded only via
 * DemoDataSeeder, never by the default DatabaseSeeder, because
 * publishing invented client quotes as genuine would mislead visitors.
 *
 * client_name is a plain column, so it serves as the match key.
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->testimonials() as $sortOrder => $testimonial) {
            Testimonial::updateOrCreate(
                ['client_name' => $testimonial['client_name']],
                [
                    'client_title' => $testimonial['client_title'],
                    'client_country' => $testimonial['client_country'],
                    'quote' => $testimonial['quote'],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }

    private function testimonials(): array
    {
        return [
            [
                'client_name' => 'أحمد العتيبي',
                'client_title' => 'الرئيس التنفيذي',
                'client_country' => 'الإمارات',
                'quote' => [
                    'ar' => 'أنجزوا تأسيس شركتنا في الرياض خلال مدة أقصر مما توقعنا، وكان التواصل واضحاً في كل مرحلة دون أي مفاجآت.',
                    'en' => 'They completed our company formation in Riyadh faster than we expected, and communication was clear at every stage with no surprises.',
                ],
            ],
            [
                'client_name' => 'سارة القحطاني',
                'client_title' => 'مديرة التوسع الإقليمي',
                'client_country' => 'الأردن',
                'quote' => [
                    'ar' => 'ساعدونا على اختيار التصنيف الصحيح للنشاط منذ البداية، وهو ما وفّر علينا وقتاً طويلاً في مرحلة الترخيص.',
                    'en' => 'They helped us choose the right activity classification from the outset, which saved us a great deal of time during licensing.',
                ],
            ],
            [
                'client_name' => 'محمد الشهري',
                'client_title' => 'شريك مؤسس',
                'client_country' => 'مصر',
                'quote' => [
                    'ar' => 'ما ميّز التجربة هو المتابعة بعد إصدار السجل التجاري، من فتح الحساب البنكي حتى إصدار التأشيرات.',
                    'en' => 'What set the experience apart was the follow-through after the Commercial Registration was issued — from opening the bank account to issuing visas.',
                ],
            ],
            [
                'client_name' => 'Daniel Whitfield',
                'client_title' => 'Managing Director',
                'client_country' => 'United Kingdom',
                'quote' => [
                    'ar' => 'فريق يفهم الأنظمة المحلية جيداً ويشرحها بلغة واضحة للمستثمر الأجنبي. أنصح بهم بثقة.',
                    'en' => 'A team that genuinely understands the local regulations and explains them in plain language to a foreign investor. I recommend them without hesitation.',
                ],
            ],
        ];
    }
}
