<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

/**
 * Adds the Visitor Session Recording section to the privacy policy on an
 * EXISTING install, without touching anything else on the page.
 *
 * WHY A MIGRATION AND NOT A RESEED. The disclosure ships in
 * PageContentSeeder for fresh installs, but that seeder is create-only by
 * design (see its class docblock): running it against a live site leaves
 * existing pages alone, so it will never deliver this section to a site
 * that is already running. And the previous behaviour — updateOrCreate —
 * would have delivered it by overwriting the whole page body, discarding
 * any wording the admin had refined in the dashboard. Neither is
 * acceptable for legal copy.
 *
 * So this appends, surgically:
 *   - Only the new section's HTML, appended to whatever body is there now.
 *   - Only if that section is not already present, per locale. Running
 *     migrate twice, or against a fresh install that seeded the section,
 *     is a no-op.
 *   - Only for the locales that actually have a body. A page with no
 *     English translation yet does not gain an English-only body here.
 *
 * The down() removes exactly what up() added, again per locale, so this is
 * genuinely reversible rather than reversible-in-name.
 *
 * IF THE ADMIN HAS ALREADY WRITTEN THEIR OWN version of this disclosure in
 * different words, the marker check below will not recognise it and the
 * section will be appended anyway, leaving the policy saying the same thing
 * twice. That is the failure this errs towards on purpose: a duplicated
 * paragraph is visible and takes a minute to remove, whereas a missing
 * disclosure is invisible and is the one with legal consequences.
 */
return new class extends Migration
{
    /**
     * The heading is the idempotency marker. It is the one string in the
     * section that is stable, unique on the page, and would have to be
     * removed deliberately.
     *
     * @var array<string, string>
     */
    private const MARKERS = [
        'ar' => '<h2>تسجيل جلسات الزوار</h2>',
        'en' => '<h2>Visitor Session Recording</h2>',
    ];

    public function up(): void
    {
        $page = Page::where('slug', 'privacy-policy')->first();

        // No page means a fresh install that has not been seeded yet; the
        // seeder will include the section when it runs.
        if (! $page) {
            return;
        }

        $changed = false;

        foreach (self::MARKERS as $locale => $marker) {
            $body = $page->getTranslation('body', $locale, false);

            // An untranslated locale stays untranslated. Appending an
            // English-only body to a page that has no English version would
            // make /en/privacy-policy render a page that is otherwise Arabic.
            if (! is_string($body) || trim($body) === '') {
                continue;
            }

            if (str_contains($body, $marker)) {
                continue;
            }

            $page->setTranslation('body', $locale, $body.$this->section($locale));
            $changed = true;
        }

        if ($changed) {
            $page->save();
        }
    }

    public function down(): void
    {
        $page = Page::where('slug', 'privacy-policy')->first();

        if (! $page) {
            return;
        }

        $changed = false;

        foreach (self::MARKERS as $locale => $marker) {
            $body = $page->getTranslation('body', $locale, false);
            $section = $this->section($locale);

            if (! is_string($body) || ! str_contains($body, $section)) {
                continue;
            }

            // Removes the exact block up() added and nothing adjacent to it.
            $page->setTranslation('body', $locale, str_replace($section, '', $body));
            $changed = true;
        }

        if ($changed) {
            $page->save();
        }
    }

    /**
     * The disclosure itself. Kept identical to the copy in
     * PageContentSeeder::seedPrivacyPolicy() so a fresh install and a
     * migrated one end up with the same policy.
     */
    private function section(string $locale): string
    {
        if ($locale === 'ar') {
            return '<h2>تسجيل جلسات الزوار</h2>'
                .'<p>إذا وافقت على ذلك عبر إشعار ملفات تعريف الارتباط، نسجّل رحلتك داخل الموقع في جلسة واحدة: الصفحات التي زرتها وترتيبها، ووقت بقائك على كل صفحة، والنموذج الذي أرسلته إن وُجد، ونوع جهازك ومتصفحك ونظام تشغيله، ومصدر وصولك إلى الموقع.</p>'
                .'<p><strong>لا نسجّل عنوان IP كاملاً لهذا الغرض إطلاقاً.</strong> يُقتطع العنوان قبل تخزينه (تُحذف الخانة الأخيرة من عنوان IPv4، ويُحتفظ بأول 48 بت فقط من عنوان IPv6)، فلا يمكن الرجوع منه إلى جهاز بعينه. كما لا نخزّن نص الروابط بعد علامة الاستفهام، ولا نص معرّف المتصفح الكامل.</p>'
                .'<p>لا يُسجَّل أي شيء من ذلك قبل موافقتك، ويُلغى التسجيل فوراً إذا كان متصفحك يرسل إشارة "عدم التتبع" (DNT) أو إشارة التحكم العام بالخصوصية (GPC)، حتى لو سبق أن وافقت. ويمكنك سحب موافقتك في أي وقت بحذف ملفات تعريف الارتباط من إعدادات المتصفح.</p>'
                .'<p>تُحذف سجلات الجلسات تلقائياً بعد انقضاء مدة الاحتفاظ المحددة (٩٠ يوماً افتراضياً)، ولا تُستخدم هذه البيانات لأي غرض غير تحسين الموقع وفهم أداء القنوات التسويقية.</p>';
        }

        return '<h2>Visitor Session Recording</h2>'
            .'<p>If you agree via the cookie notice, we record your journey through the site within a single session: the pages you viewed and the order you viewed them in, how long you spent on each, any form you submitted, your device type, browser and operating system, and where you arrived from.</p>'
            .'<p><strong>We never store a full IP address for this purpose.</strong> The address is truncated before it is stored — the final octet of an IPv4 address is discarded, and only the first 48 bits of an IPv6 address are kept — so it cannot be traced back to an individual device. We also do not store anything after the "?" in a URL, nor your full browser identification string.</p>'
            .'<p>None of this is recorded before you agree, and recording is skipped entirely if your browser sends a Do Not Track (DNT) or Global Privacy Control (GPC) signal, even if you previously agreed. You can withdraw your consent at any time by clearing this site\'s cookies in your browser settings.</p>'
            .'<p>Session records are deleted automatically once the configured retention window has passed (90 days by default). This data is used only to improve the site and understand marketing channel performance.</p>';
    }
};
