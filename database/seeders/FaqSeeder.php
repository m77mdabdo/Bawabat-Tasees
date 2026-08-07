<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Faq has no natural key column, so rows are matched on the Arabic
 * question via a JSON path lookup (works on both MySQL and SQLite) —
 * keeping the seeder idempotent without adding a schema column purely
 * for seeding.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->faqs() as $sortOrder => $faq) {
            $existing = Faq::query()->where('question->ar', $faq['question']['ar'])->first();

            if ($existing) {
                $existing->update([
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]);

                continue;
            }

            Faq::create([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    private function faqs(): array
    {
        return [
            [
                'question' => [
                    'ar' => 'كم تستغرق عملية تأسيس الشركة في المملكة؟',
                    'en' => 'How long does company formation in the Kingdom take?',
                ],
                'answer' => [
                    'ar' => 'تتراوح المدة عادة بين أسبوعين وستة أسابيع، وتعتمد على نوع النشاط وجنسية المستثمر واكتمال المستندات المطلوبة. نزوّدك بجدول زمني تقديري بعد الاستشارة الأولية.',
                    'en' => 'It typically takes between two and six weeks, depending on the business activity, the investor\'s nationality, and whether the required documents are complete. We provide an estimated timeline after the initial consultation.',
                ],
            ],
            [
                'question' => [
                    'ar' => 'هل يمكن للأجنبي تملك الشركة بالكامل؟',
                    'en' => 'Can a foreign investor own a company outright?',
                ],
                'answer' => [
                    'ar' => 'نعم، تسمح الأنظمة بالتملك الأجنبي الكامل في كثير من الأنشطة بعد الحصول على ترخيص الاستثمار. بعض الأنشطة تظل مقيدة أو تتطلب شريكاً محلياً، ونوضح لك ذلك قبل البدء.',
                    'en' => 'Yes — the regulations permit full foreign ownership across many activities once an investment licence is obtained. Some activities remain restricted or require a local partner, and we clarify this before you begin.',
                ],
            ],
            [
                'question' => [
                    'ar' => 'ما الحد الأدنى لرأس المال المطلوب؟',
                    'en' => 'What is the minimum capital requirement?',
                ],
                'answer' => [
                    'ar' => 'يختلف الحد الأدنى باختلاف النشاط والشكل القانوني. كثير من الأنشطة الخدمية لا تشترط حداً أدنى مرتفعاً، بينما تتطلب أنشطة أخرى مثل المقاولات أو التجزئة رؤوس أموال أعلى.',
                    'en' => 'The minimum varies by activity and legal form. Many service activities carry no high minimum, while others such as contracting or retail require higher capital.',
                ],
            ],
            [
                'question' => [
                    'ar' => 'هل أحتاج إلى الحضور شخصياً إلى المملكة؟',
                    'en' => 'Do I need to travel to the Kingdom in person?',
                ],
                'answer' => [
                    'ar' => 'في معظم الحالات يمكن إتمام الإجراءات عبر وكالة قانونية دون الحاجة إلى حضورك، باستثناء بعض الخطوات البنكية التي قد تتطلب حضور المدير المفوض.',
                    'en' => 'In most cases the process can be completed through a power of attorney without your attendance, except for certain banking steps that may require the authorised manager to be present.',
                ],
            ],
            [
                'question' => [
                    'ar' => 'ما المستندات المطلوبة للبدء؟',
                    'en' => 'What documents are needed to get started?',
                ],
                'answer' => [
                    'ar' => 'تشمل المستندات الأساسية صورة جواز السفر، والسجل التجاري للشركة الأم إن وُجد، والقوائم المالية المدققة. نرسل لك قائمة دقيقة بعد تحديد النشاط.',
                    'en' => 'The core documents include a passport copy, the parent company\'s Commercial Registration where applicable, and audited financial statements. We send you a precise list once the activity is confirmed.',
                ],
            ],
            [
                'question' => [
                    'ar' => 'هل تقدمون خدمات ما بعد التأسيس؟',
                    'en' => 'Do you provide post-formation services?',
                ],
                'answer' => [
                    'ar' => 'نعم، نواصل معك فتح الحساب البنكي، والتسجيل في الزكاة والضريبة والتأمينات الاجتماعية، واستخراج تأشيرات العمل، وتجديد التراخيص سنوياً.',
                    'en' => 'Yes — we continue with bank account opening, registration for Zakat, tax and social insurance, work visa issuance, and annual licence renewals.',
                ],
            ],
        ];
    }
}
