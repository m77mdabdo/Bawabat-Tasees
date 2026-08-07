<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

/**
 * Starter blog posts. Slugs are explicit so re-running matches existing
 * rows instead of creating "-2" duplicates.
 *
 * published_at is set in the PAST relative to seed time — the public blog
 * filters on `published_at <= now()`, so a future timestamp would seed
 * articles that never appear.
 *
 * Body HTML is restricted to the tag set HtmlSanitizerService allows and
 * the .article-body stylesheet targets. Draft copy pending professional
 * review.
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->articles() as $index => $article) {
            Article::updateOrCreate(
                ['slug' => $article['slug']],
                [
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'body' => $article['body'],
                    'is_published' => true,
                    'published_at' => now()->subDays(($index + 1) * 7),
                ]
            );
        }
    }

    private function articles(): array
    {
        return [
            [
                'slug' => 'steps-to-establish-a-company-in-saudi-arabia',
                'title' => [
                    'ar' => 'خطوات تأسيس شركة في السعودية: دليل عملي للمستثمر',
                    'en' => 'Establishing a Company in Saudi Arabia: A Practical Guide for Investors',
                ],
                'excerpt' => [
                    'ar' => 'نستعرض المراحل الأساسية لتأسيس شركة في المملكة، من الاستشارة الأولى وحتى بدء النشاط التجاري فعلياً.',
                    'en' => 'A walk through the core stages of establishing a company in the Kingdom, from the first consultation to actually beginning trading.',
                ],
                'body' => [
                    'ar' => '<p>يبدأ تأسيس الشركة في المملكة بتحديد النشاط التجاري بدقة، لأن هذا القرار يؤثر لاحقاً على نوع الترخيص المطلوب ورأس المال والشكل القانوني.</p><h2>تحديد الشكل القانوني</h2><p>الشركة ذات المسؤولية المحدودة هي الخيار الأكثر شيوعاً، بينما يناسب الفرع الشركات التي ترغب في التوسع دون إنشاء كيان مستقل.</p><h2>استخراج ترخيص الاستثمار</h2><p>يحتاج المستثمر الأجنبي إلى ترخيص من وزارة الاستثمار قبل قيد الشركة في السجل التجاري.</p><h2>ما بعد السجل التجاري</h2><p>لا ينتهي العمل بإصدار السجل، إذ تتبقى خطوات التسجيل لدى الزكاة والضريبة والتأمينات الاجتماعية وفتح الحساب البنكي.</p>',
                    'en' => '<p>Establishing a company in the Kingdom begins with defining the business activity precisely, because that decision later shapes the licence required, the capital, and the legal form.</p><h2>Choosing the legal form</h2><p>The limited liability company is the most common option, while a branch suits businesses expanding without creating a separate entity.</p><h2>Obtaining the investment licence</h2><p>A foreign investor needs a licence from the Ministry of Investment before the company can be entered in the Commercial Register.</p><h2>After the Commercial Registration</h2><p>The work does not end when the registration is issued — registration with Zakat, tax and social insurance, and opening the bank account, all remain.</p>',
                ],
            ],
            [
                'slug' => 'foreign-ownership-rules-explained',
                'title' => [
                    'ar' => 'التملك الأجنبي في المملكة: ما الذي تغيّر فعلياً؟',
                    'en' => 'Foreign Ownership in the Kingdom: What Has Actually Changed?',
                ],
                'excerpt' => [
                    'ar' => 'نوضح الأنشطة التي تسمح بالتملك الأجنبي الكامل، وتلك التي ما تزال مقيدة، وكيف تتحقق من وضع نشاطك.',
                    'en' => 'Which activities permit full foreign ownership, which remain restricted, and how to check where your activity stands.',
                ],
                'body' => [
                    'ar' => '<p>شهدت السنوات الأخيرة توسعاً ملموساً في الأنشطة المتاحة للمستثمر الأجنبي بنسبة تملك كاملة، ضمن توجهات رؤية المملكة 2030.</p><h2>الأنشطة المفتوحة</h2><p>تشمل غالبية الأنشطة الخدمية والتقنية والصناعية، ويكفي فيها الحصول على ترخيص الاستثمار.</p><h2>الأنشطة المقيدة</h2><p>تبقى بعض الأنشطة محصورة أو مشروطة بشريك محلي، ولذلك ننصح بالتحقق من التصنيف قبل أي التزام تعاقدي.</p>',
                    'en' => '<p>Recent years have brought a marked expansion in the activities open to foreign investors at full ownership, in line with Vision 2030.</p><h2>Open activities</h2><p>These cover most service, technology and industrial activities, where an investment licence is sufficient.</p><h2>Restricted activities</h2><p>Some activities remain limited or conditional on a local partner, so we advise verifying the classification before entering any contractual commitment.</p>',
                ],
            ],
            [
                'slug' => 'common-mistakes-in-company-formation',
                'title' => [
                    'ar' => 'خمسة أخطاء شائعة تؤخر تأسيس شركتك',
                    'en' => 'Five Common Mistakes That Delay Your Company Formation',
                ],
                'excerpt' => [
                    'ar' => 'أكثر الأسباب تكراراً وراء تأخر المعاملات، وكيف يمكن تفاديها من البداية.',
                    'en' => 'The most frequent causes of delayed applications, and how to avoid them from the outset.',
                ],
                'body' => [
                    'ar' => '<p>معظم حالات التأخير التي نراها لا تعود إلى تعقيد الأنظمة، بل إلى أخطاء يمكن تفاديها في مرحلة التحضير.</p><ul><li>اختيار تصنيف نشاط غير مطابق للعمل الفعلي.</li><li>تقديم مستندات غير مصدقة أو منتهية الصلاحية.</li><li>عدم تطابق الاسم التجاري مع الاشتراطات النظامية.</li><li>تحديد رأس مال لا يتناسب مع النشاط المطلوب.</li><li>تأجيل التسجيلات اللاحقة بعد إصدار السجل التجاري.</li></ul><p>مراجعة هذه النقاط مبكراً توفّر أسابيع من المراسلات.</p>',
                    'en' => '<p>Most of the delays we see stem not from regulatory complexity but from avoidable mistakes at the preparation stage.</p><ul><li>Selecting an activity classification that does not match the actual business.</li><li>Submitting documents that are unattested or expired.</li><li>A trade name that does not meet the regulatory conditions.</li><li>Setting capital that does not suit the intended activity.</li><li>Postponing the follow-on registrations after the Commercial Registration is issued.</li></ul><p>Reviewing these points early saves weeks of correspondence.</p>',
                ],
            ],
            [
                'slug' => 'why-riyadh-for-regional-headquarters',
                'title' => [
                    'ar' => 'لماذا تختار الشركات الرياض مقراً إقليمياً؟',
                    'en' => 'Why Are Companies Choosing Riyadh as a Regional Headquarters?',
                ],
                'excerpt' => [
                    'ar' => 'نظرة على الحوافز والعوامل التشغيلية التي تدفع الشركات العالمية إلى نقل مقارها الإقليمية.',
                    'en' => 'A look at the incentives and operational factors drawing global companies to relocate their regional headquarters.',
                ],
                'body' => [
                    'ar' => '<p>أصبحت الرياض وجهة متنامية للمقار الإقليمية للشركات متعددة الجنسيات، مدفوعةً بحوافز نظامية وحجم السوق المحلي.</p><h2>الحوافز النظامية</h2><p>يمنح البرنامج مزايا تشغيلية وتفضيلاً في التعاقدات الحكومية للشركات المستوفية للشروط.</p><h2>العامل التشغيلي</h2><p>قرب الإدارة من أكبر سوق في المنطقة يختصر دورات اتخاذ القرار ويقلل تكاليف التنقل.</p>',
                    'en' => '<p>Riyadh has become a growing destination for multinational regional headquarters, driven by regulatory incentives and the scale of the domestic market.</p><h2>Regulatory incentives</h2><p>The programme offers operational advantages and preference in government contracting for companies that meet the conditions.</p><h2>The operational factor</h2><p>Placing management close to the region\'s largest market shortens decision cycles and reduces travel costs.</p>',
                ],
            ],
        ];
    }
}
