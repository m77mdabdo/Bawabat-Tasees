<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Starter service catalogue. Slugs are supplied explicitly (rather than
 * left to HasSlug) so re-running matches existing rows instead of
 * creating "-2" duplicates.
 *
 * Draft copy pending professional review — same caveat as the page
 * content in PageContentSeeder.
 */
class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->services() as $sortOrder => $service) {
            Service::updateOrCreate(
                ['slug' => $service['slug']],
                [
                    'name' => $service['name'],
                    'summary' => $service['summary'],
                    'body' => $service['body'],
                    'requirements' => $service['requirements'],
                    'process' => $service['process'],
                    'is_active' => true,
                    'is_flagship' => $service['is_flagship'] ?? false,
                    'sort_order' => $sortOrder,
                ]
            );
        }
    }

    private function services(): array
    {
        return [
            [
                'slug' => 'llc-formation',
                'is_flagship' => true,
                'name' => [
                    'ar' => 'تأسيس شركة ذات مسؤولية محدودة',
                    'en' => 'Limited Liability Company Formation',
                ],
                'summary' => [
                    'ar' => 'الشكل القانوني الأكثر شيوعاً للمستثمرين في المملكة، بإجراءات واضحة ومسؤولية محدودة برأس المال.',
                    'en' => 'The most common legal structure for investors in the Kingdom, with clear procedures and liability limited to the capital.',
                ],
                'body' => [
                    'ar' => '<p>الشركة ذات المسؤولية المحدودة هي الخيار الأنسب لغالبية المستثمرين الراغبين في دخول السوق السعودي، إذ تجمع بين مرونة الإدارة وحماية الذمة المالية للشركاء.</p><p>نتولى عنك كامل الإجراءات: حجز الاسم التجاري، وصياغة عقد التأسيس، واستخراج السجل التجاري، والتسجيل لدى الجهات ذات العلاقة.</p>',
                    'en' => '<p>The limited liability company is the most suitable option for the majority of investors entering the Saudi market, combining management flexibility with protection of the partners\' personal assets.</p><p>We handle the entire process for you: trade name reservation, drafting the articles of association, obtaining the Commercial Registration, and registering with the relevant authorities.</p>',
                ],
                'requirements' => [
                    'ar' => 'صورة جواز سفر الشركاء، تحديد النشاط التجاري، اقتراح ثلاثة أسماء تجارية، تحديد رأس المال وحصص الشركاء.',
                    'en' => 'Copies of the partners\' passports, the intended business activity, three proposed trade names, and the capital and shareholding split.',
                ],
                'process' => [
                    'ar' => 'الاستشارة الأولية، ثم حجز الاسم التجاري، ثم صياغة عقد التأسيس وتوثيقه، ثم إصدار السجل التجاري، وأخيراً التسجيلات النظامية اللاحقة.',
                    'en' => 'Initial consultation, trade name reservation, drafting and notarising the articles of association, issuing the Commercial Registration, and finally the subsequent statutory registrations.',
                ],
            ],
            [
                'slug' => 'misa-investment-licence',
                'is_flagship' => true,
                'name' => [
                    'ar' => 'ترخيص الاستثمار الأجنبي',
                    'en' => 'Foreign Investment Licence',
                ],
                'summary' => [
                    'ar' => 'استخراج ترخيص وزارة الاستثمار، وهو الخطوة الأولى لأي مستثمر أجنبي يرغب في التملك في المملكة.',
                    'en' => 'Obtaining the Ministry of Investment licence — the first step for any foreign investor seeking ownership in the Kingdom.',
                ],
                'body' => [
                    'ar' => '<p>يُعد ترخيص الاستثمار الأجنبي البوابة النظامية التي تتيح للمستثمر غير السعودي تأسيس كيان مملوك له بالكامل أو بالشراكة، وفق النشاط المصرح به.</p><p>نساعدك على تحديد التصنيف الصحيح للنشاط وتجهيز المستندات المطلوبة بما يقلل احتمالات الرفض أو طلب الاستكمال.</p>',
                    'en' => '<p>The foreign investment licence is the statutory gateway allowing a non-Saudi investor to establish a wholly or partly owned entity, in line with the permitted activity.</p><p>We help you identify the correct activity classification and prepare the required documents in a way that reduces the likelihood of rejection or requests for additional information.</p>',
                ],
                'requirements' => [
                    'ar' => 'السجل التجاري للشركة الأم مصدقاً، القوائم المالية المدققة لآخر سنة، وقرار مجلس الإدارة بالاستثمار في المملكة.',
                    'en' => 'The attested Commercial Registration of the parent company, audited financial statements for the most recent year, and a board resolution approving the investment in the Kingdom.',
                ],
                'process' => [
                    'ar' => 'مراجعة أهلية النشاط، ثم تصديق المستندات، ثم تقديم الطلب ومتابعته حتى صدور الترخيص.',
                    'en' => 'Reviewing activity eligibility, attesting the documents, then submitting and following up the application until the licence is issued.',
                ],
            ],
            [
                'slug' => 'foreign-company-branch',
                'name' => [
                    'ar' => 'فرع شركة أجنبية',
                    'en' => 'Branch of a Foreign Company',
                ],
                'summary' => [
                    'ar' => 'تسجيل فرع للشركة الأم في المملكة دون الحاجة إلى تأسيس كيان مستقل.',
                    'en' => 'Registering a branch of the parent company in the Kingdom without establishing a separate legal entity.',
                ],
                'body' => [
                    'ar' => '<p>يتيح تسجيل الفرع للشركة الأجنبية ممارسة نشاطها في المملكة مع بقاء الكيان القانوني تابعاً للشركة الأم، وهو خيار مناسب للشركات التي ترغب في التوسع دون هيكلة كيان جديد.</p>',
                    'en' => '<p>Registering a branch allows a foreign company to operate in the Kingdom while the legal entity remains part of the parent company — a suitable option for businesses expanding without structuring a new entity.</p>',
                ],
                'requirements' => [
                    'ar' => 'قرار الشركة الأم بفتح الفرع، النظام الأساسي مصدقاً، وتعيين مدير للفرع.',
                    'en' => 'A parent-company resolution to open the branch, the attested articles of association, and the appointment of a branch manager.',
                ],
                'process' => [
                    'ar' => 'تصديق مستندات الشركة الأم، ثم استخراج ترخيص الاستثمار، ثم قيد الفرع في السجل التجاري.',
                    'en' => 'Attesting the parent company documents, obtaining the investment licence, then entering the branch in the Commercial Register.',
                ],
            ],
            [
                'slug' => 'regional-headquarters',
                'name' => [
                    'ar' => 'المقر الإقليمي',
                    'en' => 'Regional Headquarters',
                ],
                'summary' => [
                    'ar' => 'تأسيس المقر الإقليمي للشركات متعددة الجنسيات والاستفادة من الحوافز المرتبطة به.',
                    'en' => 'Establishing a regional headquarters for multinational groups and accessing the associated incentives.',
                ],
                'body' => [
                    'ar' => '<p>يمنح برنامج المقار الإقليمية الشركات العالمية مزايا نظامية وتشغيلية عند اتخاذ الرياض مركزاً لإدارة عملياتها في المنطقة.</p><p>نرافقك في تقييم مدى الاستيفاء للشروط وتجهيز الملف كاملاً.</p>',
                    'en' => '<p>The regional headquarters programme offers global companies regulatory and operational advantages when they make Riyadh the base for managing their regional operations.</p><p>We guide you through assessing eligibility and preparing the complete file.</p>',
                ],
                'requirements' => [
                    'ar' => 'إثبات وجود كيانات تابعة في عدد من الدول، وخطة توظيف وتشغيل للمقر الإقليمي.',
                    'en' => 'Evidence of subsidiaries in a number of countries, plus a staffing and operating plan for the regional headquarters.',
                ],
                'process' => [
                    'ar' => 'دراسة الأهلية، ثم إعداد خطة التشغيل، ثم تقديم الطلب واستكمال التسجيل.',
                    'en' => 'Eligibility assessment, preparing the operating plan, then submitting the application and completing registration.',
                ],
            ],
            [
                'slug' => 'post-formation-services',
                'name' => [
                    'ar' => 'خدمات ما بعد التأسيس',
                    'en' => 'Post-Formation Services',
                ],
                'summary' => [
                    'ar' => 'فتح الحساب البنكي، والتسجيل في الزكاة والضريبة والتأمينات، واستخراج التأشيرات.',
                    'en' => 'Bank account opening, registration for Zakat, tax and social insurance, and visa issuance.',
                ],
                'body' => [
                    'ar' => '<p>لا تنتهي رحلة التأسيس بإصدار السجل التجاري. نواصل معك الخطوات التشغيلية التي تجعل الشركة جاهزة فعلياً لبدء النشاط.</p>',
                    'en' => '<p>The formation journey does not end with the Commercial Registration. We continue with the operational steps that make the company genuinely ready to begin trading.</p>',
                ],
                'requirements' => [
                    'ar' => 'السجل التجاري ساري المفعول، وعقد التأسيس، وبيانات المدير المفوض.',
                    'en' => 'A valid Commercial Registration, the articles of association, and the authorised manager\'s details.',
                ],
                'process' => [
                    'ar' => 'التسجيل لدى الجهات النظامية، ثم فتح الحساب البنكي، ثم إصدار التأشيرات ورخص العمل.',
                    'en' => 'Registering with the statutory authorities, opening the bank account, then issuing visas and work permits.',
                ],
            ],
            [
                'slug' => 'commercial-registration-amendment',
                'name' => [
                    'ar' => 'تعديل السجل التجاري',
                    'en' => 'Commercial Registration Amendment',
                ],
                'summary' => [
                    'ar' => 'تعديل النشاط أو رأس المال أو الشركاء أو العنوان في السجل التجاري القائم.',
                    'en' => 'Amending the activity, capital, partners, or address on an existing Commercial Registration.',
                ],
                'body' => [
                    'ar' => '<p>تتغير احتياجات الشركة مع نموها. نتولى إجراءات التعديل النظامية لضمان توافق السجل التجاري مع الوضع الفعلي للشركة.</p>',
                    'en' => '<p>A company\'s needs change as it grows. We handle the statutory amendment procedures to keep the Commercial Registration aligned with the company\'s actual position.</p>',
                ],
                'requirements' => [
                    'ar' => 'السجل التجاري الحالي، وقرار الشركاء بالتعديل المطلوب.',
                    'en' => 'The current Commercial Registration and a partners\' resolution approving the requested amendment.',
                ],
                'process' => [
                    'ar' => 'مراجعة التعديل المطلوب، ثم توثيق القرار، ثم تحديث السجل لدى الجهة المختصة.',
                    'en' => 'Reviewing the requested amendment, notarising the resolution, then updating the register with the competent authority.',
                ],
            ],
        ];
    }
}
