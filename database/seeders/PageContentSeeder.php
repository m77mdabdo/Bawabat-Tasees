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
        $this->seedPrivacyPolicy();
        $this->seedTermsAndConditions();
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
     * Privacy Policy and Terms and Conditions — intro-only, no sections,
     * same as About. Written directly bilingual (unlike the four pages
     * above, which were seeded Arabic-only and translated to English by
     * a separate `content:translate-to-english` command run in an
     * earlier task) — this project is fully bilingual now, so new
     * content is authored in both languages from the start.
     *
     * IMPORTANT: this is professional-sounding STARTER copy, written to
     * be factually conservative for a Saudi company-formation
     * consulting business that collects contact-form data and uses
     * first-party attribution cookies — it is NOT a substitute for
     * review by a qualified lawyer and must not be treated as legally
     * final before real launch. Same standing caveat as the other four
     * pages' starter copy, just spelled out explicitly here given the
     * legal nature of this content.
     */
    private function seedPrivacyPolicy(): void
    {
        Page::updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'is_published' => true,
                'title' => ['ar' => 'سياسة الخصوصية', 'en' => 'Privacy Policy'],
                'body' => [
                    'ar' => '<p>تحرص بوابة تأسيس الشركات على خصوصية زوار موقعها والعملاء المحتملين والفعليين، وتوضح هذه السياسة طبيعة البيانات الشخصية التي نجمعها، وكيفية استخدامها وحمايتها، وحقوقك المتعلقة بها.</p>'
                        .'<h2>البيانات التي نجمعها</h2>'
                        .'<p>نجمع البيانات التي تقدمها لنا طواعية عبر نماذج التواصل والاستشارة على الموقع، وتشمل على سبيل المثال: الاسم الكامل، البريد الإلكتروني، رقم الهاتف، رقم واتساب (إن وُجد)، الجنسية وبلد الإقامة، الخدمة أو النشاط التجاري المطلوب، وأي رسالة أو تفاصيل إضافية تُدخلها في النموذج.</p>'
                        .'<p>كما نجمع بيانات تقنية محدودة تلقائياً عند تصفح الموقع، مثل عنوان IP (عند إرسال التعليقات على المقالات)، ومعرّفات مصدر الزيارة (مثل روابط الحملات الإعلانية ومعرّفات النقر مثل UTM وgclid وfbclid) عبر ملفات تعريف ارتباط من طرف أول، تُستخدم لفهم مصدر الزيارات وتحسين أداء الحملات التسويقية.</p>'
                        .'<h2>كيفية استخدام البيانات</h2>'
                        .'<ul><li>الرد على استفساراتك وطلبات الاستشارة والتواصل معك بخصوص الخدمة المطلوبة.</li><li>تقديم خدماتنا الاستشارية المتعلقة بتأسيس الشركات والاستثمار في المملكة العربية السعودية.</li><li>تحليل أداء الموقع والحملات التسويقية بشكل مجمّع لتحسين تجربة المستخدم.</li><li>الالتزام بأي متطلبات نظامية معمول بها.</li></ul>'
                        .'<p>لا نقوم ببيع بياناتك الشخصية لأي طرف ثالث. قد تتم مشاركة بيانات محدودة مع مزوّدي خدمات موثوقين (مثل منصات التحليلات والإعلانات — Google وMeta وTikTok، في حال تفعيلها من قِبلنا) لأغراض تحليل الأداء التسويقي فقط، وفق سياسات الخصوصية الخاصة بتلك المنصات.</p>'
                        .'<h2>ملفات تعريف الارتباط (الكوكيز)</h2>'
                        .'<p>يستخدم الموقع ملفات تعريف ارتباط من طرف أول لتتبع مصدر الزيارة الأولى والأخيرة بغرض فهم فعالية القنوات التسويقية، بالإضافة إلى أي أكواد تتبع تُفعَّل من لوحة التحكم (مثل Meta Pixel أو Google Analytics أو TikTok Pixel). يمكنك التحكم في ملفات تعريف الارتباط أو حظرها من إعدادات متصفحك، مع العلم أن ذلك قد يؤثر على بعض وظائف الموقع.</p>'
                        .'<h2>حماية البيانات والاحتفاظ بها</h2>'
                        .'<p>نتخذ إجراءات تقنية وتنظيمية معقولة لحماية بياناتك من الوصول أو الاستخدام غير المصرح به، ونحتفظ بالبيانات للمدة اللازمة لتحقيق الأغراض الموضحة في هذه السياسة أو للوفاء بمتطلبات نظامية.</p>'
                        .'<h2>حقوقك</h2>'
                        .'<p>يمكنك التواصل معنا في أي وقت لطلب الاطلاع على بياناتك الشخصية لدينا، أو تصحيحها، أو طلب حذفها، وفق الأنظمة المعمول بها في المملكة العربية السعودية بما في ذلك نظام حماية البيانات الشخصية.</p>'
                        .'<h2>التواصل بخصوص الخصوصية</h2>'
                        .'<p>لأي استفسار متعلق بهذه السياسة أو ببياناتك الشخصية، يُرجى التواصل معنا عبر بيانات التواصل الموضحة في صفحة "تواصل معنا".</p>',
                    'en' => '<p>Bawabat Taasees Al Sharikat is committed to protecting the privacy of our website visitors and current and prospective clients. This policy explains what personal data we collect, how we use and protect it, and your rights regarding it.</p>'
                        .'<h2>Data We Collect</h2>'
                        .'<p>We collect the data you voluntarily provide through the contact and consultation forms on this website, including: your full name, email address, phone number, WhatsApp number (if provided), nationality and country of residence, the service or business activity you are interested in, and any message or additional details you enter in the form.</p>'
                        .'<p>We also automatically collect limited technical data as you browse the site, such as your IP address (when submitting article comments), and visit-source identifiers (such as campaign links and click identifiers like UTM, gclid, and fbclid) via first-party cookies, used to understand traffic sources and improve marketing performance.</p>'
                        .'<h2>How We Use Your Data</h2>'
                        .'<ul><li>To respond to your inquiries and consultation requests and follow up regarding the service you requested.</li><li>To provide our consulting services related to company formation and investment in Saudi Arabia.</li><li>To analyze website and marketing campaign performance, in aggregate, to improve the user experience.</li><li>To comply with any applicable legal or regulatory requirements.</li></ul>'
                        .'<p>We do not sell your personal data to any third party. Limited data may be shared with trusted service providers (such as analytics and advertising platforms — Google, Meta, and TikTok, where enabled by us) solely for marketing performance analysis, subject to those platforms\' own privacy policies.</p>'
                        .'<h2>Cookies</h2>'
                        .'<p>This website uses first-party cookies to track first-touch and latest-touch visit attribution, in order to understand the effectiveness of our marketing channels, in addition to any tracking codes enabled from the dashboard (such as Meta Pixel, Google Analytics, or TikTok Pixel). You can control or block cookies through your browser settings, though this may affect some site functionality.</p>'
                        .'<h2>Data Protection and Retention</h2>'
                        .'<p>We take reasonable technical and organizational measures to protect your data from unauthorized access or use, and we retain data for as long as necessary to fulfill the purposes described in this policy or to meet applicable legal requirements.</p>'
                        .'<h2>Your Rights</h2>'
                        .'<p>You may contact us at any time to request access to, correction of, or deletion of your personal data held by us, in accordance with applicable regulations in the Kingdom of Saudi Arabia, including the Personal Data Protection Law (PDPL).</p>'
                        .'<h2>Contact Us About Privacy</h2>'
                        .'<p>For any inquiry related to this policy or your personal data, please contact us using the details on our Contact Us page.</p>',
                ],
                'meta_title' => [
                    'ar' => 'سياسة الخصوصية — بوابة تأسيس الشركات',
                    'en' => 'Privacy Policy — Bawabat Taasees Al Sharikat',
                ],
                'meta_description' => [
                    'ar' => 'تعرف على كيفية جمع بوابة تأسيس الشركات لبياناتك الشخصية واستخدامها وحمايتها.',
                    'en' => 'Learn how Bawabat Taasees Al Sharikat collects, uses, and protects your personal data.',
                ],
            ]
        );
    }

    private function seedTermsAndConditions(): void
    {
        Page::updateOrCreate(
            ['slug' => 'terms-and-conditions'],
            [
                'is_published' => true,
                'title' => ['ar' => 'الشروط والأحكام', 'en' => 'Terms and Conditions'],
                'body' => [
                    'ar' => '<p>يرجى قراءة هذه الشروط والأحكام بعناية قبل استخدام موقع بوابة تأسيس الشركات أو الاستفادة من خدماتنا. باستخدامك للموقع أو تقديم طلب استشارة أو تواصل، فإنك توافق على الالتزام بهذه الشروط.</p>'
                        .'<h2>طبيعة الخدمات</h2>'
                        .'<p>تقدّم بوابة تأسيس الشركات خدمات استشارية تتعلق بتأسيس الشركات والاستثمار في المملكة العربية السعودية، وتشمل التوجيه والمرافقة في إجراءات التأسيس والتراخيص ذات الصلة. لا تُعد هذه الخدمات استشارة قانونية أو مالية أو ضريبية رسمية، وننصح بالرجوع إلى مستشارين مختصين للحصول على استشارة رسمية عند الحاجة.</p>'
                        .'<h2>مسؤولية المستخدم</h2>'
                        .'<ul><li>تقديم معلومات صحيحة ودقيقة وكاملة عند التواصل معنا أو تعبئة نماذج الموقع.</li><li>استخدام الموقع للأغراض المشروعة فقط وعدم إساءة استخدامه بأي شكل.</li><li>تحمّل مسؤولية استيفاء المستندات والمتطلبات النظامية المطلوبة من الجهات الحكومية المختصة.</li></ul>'
                        .'<h2>نطاق الخدمة وعدم ضمان النتائج</h2>'
                        .'<p>تخضع إجراءات تأسيس الشركات والتراخيص للأنظمة والقرارات الصادرة عن الجهات الحكومية المختصة في المملكة العربية السعودية، والتي تقع خارج نطاق سيطرتنا المباشر. وبالتالي، فإننا نبذل عناية مهنية معقولة في تقديم خدماتنا الاستشارية دون تقديم أي ضمان بشأن الموافقة على أي طلب أو الجدول الزمني لإتمامه من قِبل الجهات الحكومية.</p>'
                        .'<h2>الملكية الفكرية</h2>'
                        .'<p>جميع المحتويات المنشورة على هذا الموقع، بما في ذلك النصوص والشعارات والتصاميم، مملوكة لبوابة تأسيس الشركات أو مرخّصة لها، ولا يجوز إعادة استخدامها أو نسخها دون إذن كتابي مسبق.</p>'
                        .'<h2>حدود المسؤولية</h2>'
                        .'<p>لا تتحمل بوابة تأسيس الشركات المسؤولية عن أي أضرار غير مباشرة أو تبعية قد تنشأ عن استخدام الموقع أو الاعتماد على المحتوى المنشور فيه، إلى أقصى حد يسمح به النظام المعمول به.</p>'
                        .'<h2>القانون الواجب التطبيق</h2>'
                        .'<p>تخضع هذه الشروط والأحكام لأنظمة المملكة العربية السعودية، وتختص المحاكم السعودية المختصة بالنظر في أي نزاع ينشأ عنها.</p>'
                        .'<h2>التعديلات على الشروط</h2>'
                        .'<p>نحتفظ بالحق في تحديث هذه الشروط والأحكام من وقت لآخر، ويُعد استمرارك في استخدام الموقع بعد نشر أي تعديل موافقة ضمنية عليه.</p>'
                        .'<h2>التواصل معنا</h2>'
                        .'<p>لأي استفسار بخصوص هذه الشروط والأحكام، يُرجى التواصل معنا عبر بيانات التواصل الموضحة في صفحة "تواصل معنا".</p>',
                    'en' => '<p>Please read these Terms and Conditions carefully before using the Bawabat Taasees Al Sharikat website or engaging our services. By using this website or submitting a consultation or contact request, you agree to be bound by these terms.</p>'
                        .'<h2>Nature of Our Services</h2>'
                        .'<p>Bawabat Taasees Al Sharikat provides consulting services related to company formation and investment in the Kingdom of Saudi Arabia, including guidance and support through formation procedures and related licensing. These services do not constitute formal legal, financial, or tax advice, and we recommend consulting a qualified legal or financial advisor for formal advice when needed.</p>'
                        .'<h2>User Responsibilities</h2>'
                        .'<ul><li>Provide accurate, correct, and complete information when contacting us or filling out forms on this site.</li><li>Use this website for lawful purposes only and refrain from misusing it in any way.</li><li>Take responsibility for fulfilling any documentation and regulatory requirements requested by the relevant government authorities.</li></ul>'
                        .'<h2>Service Scope and No Guarantee of Outcome</h2>'
                        .'<p>Company formation and licensing procedures are subject to the regulations and decisions of the relevant government authorities in Saudi Arabia, which are outside our direct control. Accordingly, we exercise reasonable professional care in providing our consulting services, without guaranteeing the approval of any application or the timeline for its completion by government authorities.</p>'
                        .'<h2>Intellectual Property</h2>'
                        .'<p>All content published on this website, including text, logos, and designs, is owned by or licensed to Bawabat Taasees Al Sharikat, and may not be reused or copied without prior written permission.</p>'
                        .'<h2>Limitation of Liability</h2>'
                        .'<p>Bawabat Taasees Al Sharikat shall not be liable for any indirect or consequential damages arising from the use of this website or reliance on its published content, to the fullest extent permitted by applicable law.</p>'
                        .'<h2>Governing Law</h2>'
                        .'<p>These Terms and Conditions are governed by the laws and regulations of the Kingdom of Saudi Arabia, and the competent Saudi courts shall have jurisdiction over any dispute arising from them.</p>'
                        .'<h2>Changes to These Terms</h2>'
                        .'<p>We reserve the right to update these Terms and Conditions from time to time. Your continued use of the website after any changes are published constitutes your acceptance of them.</p>'
                        .'<h2>Contact Us</h2>'
                        .'<p>For any questions about these Terms and Conditions, please contact us using the details on our Contact Us page.</p>',
                ],
                'meta_title' => [
                    'ar' => 'الشروط والأحكام — بوابة تأسيس الشركات',
                    'en' => 'Terms and Conditions — Bawabat Taasees Al Sharikat',
                ],
                'meta_description' => [
                    'ar' => 'الشروط والأحكام الخاصة باستخدام موقع وخدمات بوابة تأسيس الشركات.',
                    'en' => 'The terms and conditions governing the use of Bawabat Taasees Al Sharikat\'s website and services.',
                ],
            ]
        );
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
