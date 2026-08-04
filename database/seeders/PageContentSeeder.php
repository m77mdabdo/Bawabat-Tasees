<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Seeds starter copy for the four fixed pages this project needs. This is
 * professional draft copy written to be factually conservative (no
 * unverifiable statistics, no specific legal claims) — it is a starting
 * point for the admin to review and refine via the dashboard Pages screen,
 * not final legally-reviewed content.
 */
class PageContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAbout();
        $this->seedWhyInvest();
        $this->seedFormationProcess();
        $this->seedRequiredDocuments();
    }

    private function seedAbout(): void
    {
        // About is intro-only per this task's scope — no sections.
        Page::updateOrCreate(
            ['slug' => 'about'],
            [
                'is_published' => true,
                'title' => ['ar' => 'من نحن'],
                'body' => [
                    'ar' => '<p>بوابة تأسيس الشركات شريكك الموثوق لتأسيس الأعمال والاستثمار في المملكة العربية السعودية. نرافق المستثمرين الأجانب والمحليين في كل خطوة من رحلة التأسيس، من الاستشارة الأولى وحتى استخراج السجل التجاري وانطلاق النشاط التجاري.</p><p>يجمع فريقنا بين المعرفة العميقة بالأنظمة واللوائح المحلية وخبرة عملية في التعامل مع الجهات الحكومية المختصة، لنقدم لعملائنا مساراً واضحاً وسلساً نحو تأسيس شركاتهم بثقة.</p>',
                ],
                'meta_title' => ['ar' => 'من نحن — بوابة تأسيس الشركات'],
                'meta_description' => ['ar' => 'تعرف على بوابة تأسيس الشركات، شريكك الموثوق لتأسيس الأعمال والاستثمار في المملكة العربية السعودية.'],
            ]
        );
    }

    private function seedWhyInvest(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'why-invest-saudi-arabia'],
            [
                'is_published' => true,
                'title' => ['ar' => 'لماذا تستثمر في السعودية'],
                'body' => [
                    'ar' => '<p>تشهد المملكة العربية السعودية تحولاً اقتصادياً واسعاً في إطار رؤية المملكة 2030، ما يفتح آفاقاً واعدة أمام المستثمرين من مختلف القطاعات. فيما يلي أبرز العوامل التي تجعل من السعودية وجهة استثمارية جديرة بالاهتمام.</p>',
                ],
                'meta_title' => ['ar' => 'لماذا تستثمر في السعودية — بوابة تأسيس الشركات'],
                'meta_description' => ['ar' => 'أبرز عوامل الجذب الاستثماري في المملكة العربية السعودية للمستثمرين المحليين والأجانب.'],
            ]
        );

        $this->replaceSections($page, [
            ['key' => 'vision-2030', 'title' => 'التوافق مع رؤية 2030', 'description' => 'استراتيجية وطنية شاملة تعمل على تنويع الاقتصاد وفتح قطاعات جديدة أمام الاستثمار المحلي والأجنبي.', 'icon' => 'chart-line'],
            ['key' => 'foreign-ownership', 'title' => 'تملك أجنبي كامل في قطاعات عديدة', 'description' => 'تتيح الأنظمة الحالية للمستثمرين الأجانب تملك 100% من شركاتهم في كثير من الأنشطة التجارية دون الحاجة لشريك محلي.', 'icon' => 'building-office'],
            ['key' => 'strategic-location', 'title' => 'موقع استراتيجي', 'description' => 'تتوسط المملكة قارات آسيا وأفريقيا وأوروبا، ما يمنحها موقعاً لوجستياً مميزاً للوصول إلى أسواق إقليمية وعالمية.', 'icon' => 'map'],
            ['key' => 'growing-economy', 'title' => 'اقتصاد متنامٍ', 'description' => 'يشهد الاقتصاد السعودي نمواً مستمراً مدعوماً باستثمارات ضخمة في البنية التحتية والقطاعات غير النفطية.', 'icon' => 'trending-up'],
            ['key' => 'ease-of-doing-business', 'title' => 'تسهيلات في بيئة الأعمال', 'description' => 'تعمل الجهات الحكومية على تبسيط إجراءات التأسيس والترخيص من خلال منصات رقمية متكاملة.', 'icon' => 'clipboard-check'],
            ['key' => 'government-incentives', 'title' => 'حوافز حكومية', 'description' => 'تقدم الجهات المختصة برامج وحوافز متنوعة لدعم المستثمرين في قطاعات ذات أولوية استراتيجية.', 'icon' => 'gift'],
        ]);
    }

    private function seedFormationProcess(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'formation-process'],
            [
                'is_published' => true,
                'title' => ['ar' => 'خطوات تأسيس الشركة'],
                'body' => [
                    'ar' => '<p>تمر عملية تأسيس شركة في المملكة العربية السعودية بعدة مراحل أساسية. فيما يلي نظرة عامة على الخطوات المعتادة — قد تختلف التفاصيل الدقيقة حسب النشاط التجاري وجنسية المستثمر ونوع الترخيص المطلوب.</p>',
                ],
                'meta_title' => ['ar' => 'خطوات تأسيس الشركة — بوابة تأسيس الشركات'],
                'meta_description' => ['ar' => 'الخطوات الأساسية لتأسيس شركة في المملكة العربية السعودية، من الاستشارة الأولى وحتى الانطلاق التشغيلي.'],
            ]
        );

        $this->replaceSections($page, [
            ['key' => 'step-1', 'title' => 'الاستشارة الأولية', 'description' => 'نناقش أهدافك الاستثمارية ونشاطك التجاري المستهدف لتحديد أفضل مسار قانوني وإداري للتأسيس.', 'icon' => 'chat-bubble'],
            ['key' => 'step-2', 'title' => 'حجز الاسم التجاري', 'description' => 'اختيار وحجز الاسم التجاري للشركة لدى الجهة المختصة.', 'icon' => 'tag'],
            ['key' => 'step-3', 'title' => 'إعداد عقد التأسيس ولائحته الداخلية', 'description' => 'صياغة عقد التأسيس والنظام الأساسي للشركة وفق النشاط والشكل القانوني المختار.', 'icon' => 'document-text'],
            ['key' => 'step-4', 'title' => 'الحصول على ترخيص الاستثمار (إن لزم الأمر)', 'description' => 'استخراج الترخيص اللازم من الجهة المختصة بالاستثمار الأجنبي للأنشطة التي تتطلب ذلك.', 'icon' => 'identification'],
            ['key' => 'step-5', 'title' => 'استخراج السجل التجاري', 'description' => 'تسجيل الشركة رسمياً والحصول على السجل التجاري.', 'icon' => 'clipboard-document'],
            ['key' => 'step-6', 'title' => 'فتح الحساب البنكي للشركة', 'description' => 'فتح حساب بنكي مؤسسي باسم الشركة لدى أحد البنوك المحلية المعتمدة.', 'icon' => 'banknotes'],
            ['key' => 'step-7', 'title' => 'التسجيل في المنصات الحكومية والانطلاق التشغيلي', 'description' => 'إتمام التسجيل في المنصات الحكومية ذات الصلة (كالتأمينات الاجتماعية والزكاة والضريبة) والبدء الفعلي في مزاولة النشاط.', 'icon' => 'rocket-launch'],
        ]);
    }

    private function seedRequiredDocuments(): void
    {
        $page = Page::updateOrCreate(
            ['slug' => 'required-documents'],
            [
                'is_published' => true,
                'title' => ['ar' => 'المستندات المطلوبة'],
                'body' => [
                    'ar' => '<p>فيما يلي المستندات الشائع طلبها عند تأسيس شركة في المملكة العربية السعودية. تختلف المتطلبات الفعلية حسب نوع النشاط والجنسية والشكل القانوني للشركة، وسيقوم فريقنا بتزويدك بقائمة دقيقة بعد الاستشارة الأولية.</p>',
                ],
                'meta_title' => ['ar' => 'المستندات المطلوبة — بوابة تأسيس الشركات'],
                'meta_description' => ['ar' => 'قائمة عامة بالمستندات الشائع طلبها لتأسيس شركة في المملكة العربية السعودية.'],
            ]
        );

        $this->replaceSections($page, [
            ['key' => 'passport-copy', 'title' => 'نسخة من جواز السفر', 'description' => 'نسخة سارية المفعول من جواز سفر المستثمر أو المستثمرين الشركاء.', 'icon' => 'identification'],
            ['key' => 'parent-company-docs', 'title' => 'مستندات الشركة الأم (إن وجدت)', 'description' => 'مستندات تسجيل الشركة من بلد المنشأ في حال كان المستثمر شركة قائمة وليس فرداً.', 'icon' => 'building-office'],
            ['key' => 'trade-name-options', 'title' => 'خيارات الاسم التجاري المقترح', 'description' => 'عدة خيارات مقترحة للاسم التجاري تسهيلاً لعملية الحجز والاعتماد.', 'icon' => 'tag'],
            ['key' => 'power-of-attorney', 'title' => 'وكالة رسمية (عند الحاجة)', 'description' => 'وكالة موثقة في حال الاستعانة بممثل لإنجاز إجراءات التأسيس نيابة عن المستثمر.', 'icon' => 'document-check'],
            ['key' => 'business-profile', 'title' => 'ملف تعريفي بالنشاط', 'description' => 'نبذة عن النشاط التجاري المستهدف وخطة العمل المبدئية.', 'icon' => 'briefcase'],
            ['key' => 'correspondence-address', 'title' => 'عنوان مراسلة صالح', 'description' => 'عنوان بريد إلكتروني ورقم تواصل صالحين للمراسلات الرسمية أثناء إجراءات التأسيس.', 'icon' => 'envelope'],
        ]);
    }

    /**
     * Idempotently replace a page's sections: sections aren't guaranteed
     * unique by key alone (only page-scoped), so re-running this seeder
     * clears and re-creates them rather than risking duplicates or stale
     * leftovers if the starter content list changes.
     */
    private function replaceSections(Page $page, array $sections): void
    {
        $page->sections()->delete();

        foreach ($sections as $index => $section) {
            $page->sections()->create([
                'key' => $section['key'],
                'sort_order' => $index,
                'is_active' => true,
                'content' => [
                    'title' => ['ar' => $section['title']],
                    'description' => ['ar' => $section['description']],
                    'icon' => $section['icon'],
                ],
            ]);
        }
    }
}
