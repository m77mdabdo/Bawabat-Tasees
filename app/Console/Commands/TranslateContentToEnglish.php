<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Country;
use App\Models\Faq;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\Testimonial;
use App\Services\Cms\HtmlSanitizerService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TranslateContentToEnglish extends Command
{
    protected $signature = 'content:translate-to-english {--force : Overwrite English content that already exists, instead of skipping it}';

    protected $description = 'Writes professional English translations into every translatable field that currently has Arabic content and no English content.';

    /**
     * Every English value here was written by hand for this task — there
     * is no machine-translation API wired into this project, and none was
     * requested. Keyed by the EXACT Arabic source text (a stable, simple
     * lookup that works well given this project's content is authored
     * once via seeders/dashboard, not free-form user text) — anything not
     * covered here is reported as "no translation available" rather than
     * silently skipped or guessed at, so a future run over new content
     * makes the gap visible instead of leaving a blank English field.
     *
     * HTML body fields are stored here WITH their wrapping tags (matching
     * the source's structure) and are still passed through
     * HtmlSanitizerService::sanitizeArticleBody() before saving, exactly
     * like every other HTML body write in this project — translations
     * authored by this command get no special bypass of that sanitizer.
     *
     * Brand name: translated consistently as "Bawabat Taasees Al
     * Sharikat" everywhere the name itself appears (see lang/en/site.php
     * for the full rationale — the same decision applies here).
     */
    private array $translations = [
        // Page: about
        'من نحن' => 'About Us',
        '<p>بوابة تأسيس الشركات شريكك الموثوق لتأسيس الأعمال والاستثمار في المملكة العربية السعودية. نرافق المستثمرين الأجانب والمحليين في كل خطوة من رحلة التأسيس، من الاستشارة الأولى وحتى استخراج السجل التجاري وانطلاق النشاط التجاري.</p><p>يجمع فريقنا بين المعرفة العميقة بالأنظمة واللوائح المحلية وخبرة عملية في التعامل مع الجهات الحكومية المختصة، لنقدم لعملائنا مساراً واضحاً وسلساً نحو تأسيس شركاتهم بثقة.</p>' => '<p>Bawabat Taasees Al Sharikat is your trusted partner for business formation and investment in the Kingdom of Saudi Arabia. We guide foreign and local investors through every step of the formation journey, from the first consultation through Commercial Registration and the launch of operations.</p><p>Our team combines deep knowledge of local regulations with hands-on experience working with the relevant government authorities, giving our clients a clear, seamless path to establishing their companies with confidence.</p>',
        'من نحن — بوابة تأسيس الشركات' => 'About Us — Bawabat Taasees Al Sharikat',
        'تعرف على بوابة تأسيس الشركات، شريكك الموثوق لتأسيس الأعمال والاستثمار في المملكة العربية السعودية.' => 'Learn about Bawabat Taasees Al Sharikat, your trusted partner for business formation and investment in Saudi Arabia.',

        // Page: why-invest-saudi-arabia
        'لماذا تستثمر في السعودية' => 'Why Invest in Saudi Arabia',
        '<p>تشهد المملكة العربية السعودية تحولاً اقتصادياً واسعاً في إطار رؤية المملكة 2030، ما يفتح آفاقاً واعدة أمام المستثمرين من مختلف القطاعات. فيما يلي أبرز العوامل التي تجعل من السعودية وجهة استثمارية جديرة بالاهتمام.</p>' => '<p>Saudi Arabia is undergoing a sweeping economic transformation under Vision 2030, opening promising opportunities for investors across a wide range of sectors. Below are the key factors that make Saudi Arabia a compelling investment destination.</p>',
        'لماذا تستثمر في السعودية — بوابة تأسيس الشركات' => 'Why Invest in Saudi Arabia — Bawabat Taasees Al Sharikat',
        'أبرز عوامل الجذب الاستثماري في المملكة العربية السعودية للمستثمرين المحليين والأجانب.' => 'Key investment drivers in the Kingdom of Saudi Arabia for local and foreign investors.',

        // Page: formation-process
        'خطوات تأسيس الشركة' => 'Company Formation Process',
        '<p>تمر عملية تأسيس شركة في المملكة العربية السعودية بعدة مراحل أساسية. فيما يلي نظرة عامة على الخطوات المعتادة — قد تختلف التفاصيل الدقيقة حسب النشاط التجاري وجنسية المستثمر ونوع الترخيص المطلوب.</p>' => "<p>Establishing a company in Saudi Arabia involves several core stages. Below is an overview of the typical steps — exact details may vary depending on the business activity, the investor's nationality, and the type of licence required.</p>",
        'خطوات تأسيس الشركة — بوابة تأسيس الشركات' => 'Company Formation Process — Bawabat Taasees Al Sharikat',
        'الخطوات الأساسية لتأسيس شركة في المملكة العربية السعودية، من الاستشارة الأولى وحتى الانطلاق التشغيلي.' => 'The core steps to establishing a company in Saudi Arabia, from the first consultation through operational launch.',

        // Page: required-documents
        'المستندات المطلوبة' => 'Required Documents',
        '<p>فيما يلي المستندات الشائع طلبها عند تأسيس شركة في المملكة العربية السعودية. تختلف المتطلبات الفعلية حسب نوع النشاط والجنسية والشكل القانوني للشركة، وسيقوم فريقنا بتزويدك بقائمة دقيقة بعد الاستشارة الأولية.</p>' => '<p>Below are the documents commonly requested when establishing a company in Saudi Arabia. Actual requirements vary depending on the business activity, nationality, and legal form of the company — our team will provide you with a precise list following your initial consultation.</p>',
        'المستندات المطلوبة — بوابة تأسيس الشركات' => 'Required Documents — Bawabat Taasees Al Sharikat',
        'قائمة عامة بالمستندات الشائع طلبها لتأسيس شركة في المملكة العربية السعودية.' => 'A general list of documents commonly required to establish a company in Saudi Arabia.',

        // PageSection: why-invest-saudi-arabia
        'التوافق مع رؤية 2030' => 'Aligned with Vision 2030',
        'استراتيجية وطنية شاملة تعمل على تنويع الاقتصاد وفتح قطاعات جديدة أمام الاستثمار المحلي والأجنبي.' => 'A comprehensive national strategy diversifying the economy and opening new sectors to local and foreign investment.',
        'تملك أجنبي كامل في قطاعات عديدة' => '100% Foreign Ownership in Many Sectors',
        'تتيح الأنظمة الحالية للمستثمرين الأجانب تملك 100% من شركاتهم في كثير من الأنشطة التجارية دون الحاجة لشريك محلي.' => 'Current regulations allow foreign investors to own 100% of their companies in many business activities, with no local partner required.',
        'موقع استراتيجي' => 'Strategic Location',
        'تتوسط المملكة قارات آسيا وأفريقيا وأوروبا، ما يمنحها موقعاً لوجستياً مميزاً للوصول إلى أسواق إقليمية وعالمية.' => 'Saudi Arabia sits at the crossroads of Asia, Africa, and Europe, giving it an outstanding logistical position for reaching regional and global markets.',
        'اقتصاد متنامٍ' => 'A Growing Economy',
        'يشهد الاقتصاد السعودي نمواً مستمراً مدعوماً باستثمارات ضخمة في البنية التحتية والقطاعات غير النفطية.' => 'The Saudi economy continues to grow, backed by substantial investment in infrastructure and non-oil sectors.',
        'تسهيلات في بيئة الأعمال' => 'A Streamlined Business Environment',
        'تعمل الجهات الحكومية على تبسيط إجراءات التأسيس والترخيص من خلال منصات رقمية متكاملة.' => 'Government authorities are simplifying formation and licensing procedures through integrated digital platforms.',
        'حوافز حكومية' => 'Government Incentives',
        'تقدم الجهات المختصة برامج وحوافز متنوعة لدعم المستثمرين في قطاعات ذات أولوية استراتيجية.' => 'Relevant authorities offer a range of programs and incentives to support investors in strategically prioritised sectors.',

        // PageSection: formation-process
        'الاستشارة الأولية' => 'Initial Consultation',
        'نناقش أهدافك الاستثمارية ونشاطك التجاري المستهدف لتحديد أفضل مسار قانوني وإداري للتأسيس.' => 'We discuss your investment goals and target business activity to determine the best legal and administrative path for formation.',
        'حجز الاسم التجاري' => 'Trade Name Reservation',
        'اختيار وحجز الاسم التجاري للشركة لدى الجهة المختصة.' => "Selecting and reserving your company's trade name with the relevant authority.",
        'إعداد عقد التأسيس ولائحته الداخلية' => 'Drafting the Articles of Association and Bylaws',
        'صياغة عقد التأسيس والنظام الأساسي للشركة وفق النشاط والشكل القانوني المختار.' => "Drafting the company's Articles of Association and bylaws in line with the chosen activity and legal form.",
        'الحصول على ترخيص الاستثمار (إن لزم الأمر)' => 'Obtaining an Investment Licence (if required)',
        'استخراج الترخيص اللازم من الجهة المختصة بالاستثمار الأجنبي للأنشطة التي تتطلب ذلك.' => 'Securing the necessary licence from the foreign investment authority for activities that require one.',
        'استخراج السجل التجاري' => 'Issuing the Commercial Registration',
        'تسجيل الشركة رسمياً والحصول على السجل التجاري.' => 'Formally registering the company and obtaining its Commercial Registration (CR).',
        'فتح الحساب البنكي للشركة' => "Opening the Company's Bank Account",
        'فتح حساب بنكي مؤسسي باسم الشركة لدى أحد البنوك المحلية المعتمدة.' => "Opening a corporate bank account in the company's name with an approved local bank.",
        'التسجيل في المنصات الحكومية والانطلاق التشغيلي' => 'Registering with Government Platforms and Launching Operations',
        'إتمام التسجيل في المنصات الحكومية ذات الصلة (كالتأمينات الاجتماعية والزكاة والضريبة) والبدء الفعلي في مزاولة النشاط.' => 'Completing registration with the relevant government platforms (such as GOSI and ZATCA) and formally beginning operations.',

        // PageSection: required-documents
        'نسخة من جواز السفر' => 'Passport Copy',
        'نسخة سارية المفعول من جواز سفر المستثمر أو المستثمرين الشركاء.' => 'A valid copy of the passport of the investor or investing partners.',
        'مستندات الشركة الأم (إن وجدت)' => 'Parent Company Documents (if applicable)',
        'مستندات تسجيل الشركة من بلد المنشأ في حال كان المستثمر شركة قائمة وليس فرداً.' => "Registration documents from the company's home country, if the investor is an existing company rather than an individual.",
        'خيارات الاسم التجاري المقترح' => 'Proposed Trade Name Options',
        'عدة خيارات مقترحة للاسم التجاري تسهيلاً لعملية الحجز والاعتماد.' => 'Several proposed trade name options to streamline the reservation and approval process.',
        'وكالة رسمية (عند الحاجة)' => 'Power of Attorney (if needed)',
        'وكالة موثقة في حال الاستعانة بممثل لإنجاز إجراءات التأسيس نيابة عن المستثمر.' => "A notarised power of attorney if a representative will complete the formation procedures on the investor's behalf.",
        'ملف تعريفي بالنشاط' => 'Business Activity Profile',
        'نبذة عن النشاط التجاري المستهدف وخطة العمل المبدئية.' => 'An overview of the target business activity and preliminary business plan.',
        'عنوان مراسلة صالح' => 'Valid Correspondence Address',
        'عنوان بريد إلكتروني ورقم تواصل صالحين للمراسلات الرسمية أثناء إجراءات التأسيس.' => 'A valid email address and contact number for official correspondence during the formation process.',
    ];

    /**
     * Pages whose Arabic starter copy was already flagged (in an earlier
     * task's TASKS.md entry) as draft content pending legal/professional
     * review, not final. Their English translations carry the exact same
     * caveat — translating draft copy doesn't make it final. Restated
     * here, and in this task's TASKS.md entry, per this task's explicit
     * instruction not to present the English versions as more final than
     * the Arabic originals.
     */
    private array $unreviewedPageSlugs = [
        'about',
        'why-invest-saudi-arabia',
        'formation-process',
        'required-documents',
    ];

    private int $totalTranslated = 0;

    private int $totalSkippedExisting = 0;

    private array $untranslatable = [];

    public function handle(HtmlSanitizerService $sanitizer): int
    {
        // Laravel reuses the same resolved Command instance across
        // multiple Artisan::call() invocations within one process (e.g.
        // the idempotency test calling this command twice in a row) —
        // reset instance state explicitly rather than relying on a fresh
        // object per run.
        $this->totalTranslated = 0;
        $this->totalSkippedExisting = 0;
        $this->untranslatable = [];

        $force = (bool) $this->option('force');

        $summary = [];

        $summary['Page'] = $this->translatePages($sanitizer, $force);
        $summary['PageSection'] = $this->translatePageSections($force);
        $summary['Service'] = $this->translateModel(Service::class, ['name', 'summary', 'body', 'requirements', 'process'], $force, htmlFields: ['body']);
        $summary['Country'] = $this->translateModel(Country::class, ['name', 'notes'], $force);
        $summary['Faq'] = $this->translateModel(Faq::class, ['question', 'answer'], $force);
        $summary['Article'] = $this->translateModel(Article::class, ['title', 'excerpt', 'body'], $force, htmlFields: ['body']);
        $summary['Testimonial'] = $this->translateModel(Testimonial::class, ['quote'], $force);
        $summary['SeoMeta'] = $this->translateModel(SeoMeta::class, ['meta_title', 'meta_description'], $force);

        $this->printSummary($summary);

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function translateModel(string $modelClass, array $fields, bool $force, array $htmlFields = []): array
    {
        $stats = ['translated' => 0, 'skipped_existing' => 0, 'skipped_no_translation' => 0];

        foreach ($modelClass::all() as $model) {
            $dirty = false;

            foreach ($fields as $field) {
                $ar = $model->getTranslation($field, 'ar', false);

                if (empty($ar)) {
                    continue;
                }

                $enExisting = $model->getTranslation($field, 'en', false);

                if (! empty($enExisting) && ! $force) {
                    $stats['skipped_existing']++;

                    continue;
                }

                $en = $this->translations[$ar] ?? null;

                if ($en === null) {
                    $stats['skipped_no_translation']++;
                    $this->untranslatable[] = $modelClass.'#'.$model->getKey()." [{$field}]: ".Str::limit($ar, 60);

                    continue;
                }

                if (in_array($field, $htmlFields, true)) {
                    $en = app(HtmlSanitizerService::class)->sanitizeArticleBody($en);
                }

                $model->setTranslation($field, 'en', $en);
                $dirty = true;
                $stats['translated']++;
            }

            if ($dirty) {
                $model->save();
            }
        }

        $this->totalTranslated += $stats['translated'];
        $this->totalSkippedExisting += $stats['skipped_existing'];

        return $stats;
    }

    private function translatePages(HtmlSanitizerService $sanitizer, bool $force): array
    {
        return $this->translateModel(Page::class, ['title', 'body', 'meta_title', 'meta_description'], $force, htmlFields: ['body']);
    }

    /**
     * PageSection.content is a single JSON blob (title/description/icon
     * per locale), not a spatie/laravel-translatable field — no
     * getTranslation()/setTranslation() available, so this reads and
     * writes the array directly.
     */
    private function translatePageSections(bool $force): array
    {
        $stats = ['translated' => 0, 'skipped_existing' => 0, 'skipped_no_translation' => 0];

        foreach (PageSection::all() as $section) {
            $content = $section->content;
            $dirty = false;

            foreach (['title', 'description'] as $field) {
                $ar = $content[$field]['ar'] ?? null;

                if (empty($ar)) {
                    continue;
                }

                $enExisting = $content[$field]['en'] ?? null;

                if (! empty($enExisting) && ! $force) {
                    $stats['skipped_existing']++;

                    continue;
                }

                $en = $this->translations[$ar] ?? null;

                if ($en === null) {
                    $stats['skipped_no_translation']++;
                    $this->untranslatable[] = 'PageSection#'.$section->getKey()." [{$field}]: ".Str::limit($ar, 60);

                    continue;
                }

                $content[$field]['en'] = $en;
                $dirty = true;
                $stats['translated']++;
            }

            if ($dirty) {
                $section->content = $content;
                $section->save();
            }
        }

        $this->totalTranslated += $stats['translated'];
        $this->totalSkippedExisting += $stats['skipped_existing'];

        return $stats;
    }

    private function printSummary(array $summary): void
    {
        $this->newLine();
        $this->info('English translation summary:');

        $this->table(
            ['Model', 'Translated', 'Skipped (already English)', 'Skipped (no translation available)'],
            collect($summary)->map(fn ($stats, $model) => [
                $model,
                $stats['translated'],
                $stats['skipped_existing'],
                $stats['skipped_no_translation'],
            ])->values()->all()
        );

        if ($this->untranslatable) {
            $this->newLine();
            $this->warn('Fields with Arabic content but no translation available in this command\'s dictionary (needs manual authoring, not guessed):');
            foreach ($this->untranslatable as $line) {
                $this->line("  - {$line}");
            }
        }

        $this->newLine();
        $this->line("Total fields translated: {$this->totalTranslated}");
        $this->line("Total fields already had English content (skipped): {$this->totalSkippedExisting}");

        $unreviewed = implode(', ', $this->unreviewedPageSlugs);
        $this->newLine();
        $this->warn("Reminder: the Arabic starter copy for [{$unreviewed}] was flagged in an earlier task as draft content pending legal/professional review — their new English translations carry the exact same caveat and are not more final than the Arabic originals.");
    }
}
