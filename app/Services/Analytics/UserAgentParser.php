<?php

namespace App\Services\Analytics;

/**
 * Coarse device / browser / OS from a user agent string, plus bot detection.
 *
 * WHY NOT A LIBRARY. The mature options (matomo/device-detector,
 * jenssegers/agent) carry thousands of regexes and exist to answer questions
 * this project does not ask — exact model, browser version, bot vendor. The
 * requirement here is three buckets for a dashboard column. A dependency
 * that large for that is a maintenance liability, and the parse runs on
 * every tracked page view.
 *
 * The trade is accuracy on the long tail: an obscure browser reports as
 * "Other" rather than by name. That is the correct failure — an unknown
 * browser is genuinely unknown, and nothing downstream branches on this.
 *
 * ORDER MATTERS in both lists below and is the only subtle part:
 *   - Edge and Opera both carry "Chrome" in their UA, Chrome carries
 *     "Safari", so the specific names have to be tested first.
 *   - iPadOS reports as "Macintosh", so the iPad check precedes the Mac one.
 * Each list is ordered most-specific first; do not sort them.
 */
class UserAgentParser
{
    public const DEVICE_MOBILE = 'mobile';

    public const DEVICE_TABLET = 'tablet';

    public const DEVICE_DESKTOP = 'desktop';

    /**
     * Substrings that mark a request as automated.
     *
     * Deliberately broad and case-insensitive. A false positive costs one
     * unrecorded session; a false negative pollutes the data this feature
     * exists to produce, and a crawler hitting 200 pages would also burn
     * through a session's event cap. When in doubt, this errs towards "bot".
     *
     * The bare tokens 'bot', 'crawl', 'spider' catch the overwhelming
     * majority without naming anyone. The named entries below them are the
     * ones that do NOT contain those tokens, which is the only reason to
     * list a vendor at all.
     */
    private const BOT_SIGNATURES = [
        'bot', 'crawl', 'spider', 'slurp', 'scraper', 'archiver',
        'facebookexternalhit', 'ia_archiver', 'mediapartners',
        'lighthouse', 'pagespeed', 'gtmetrix', 'pingdom', 'uptimerobot',
        'headlesschrome', 'phantomjs', 'puppeteer', 'playwright', 'selenium',
        'curl/', 'wget/', 'python-requests', 'python-urllib', 'go-http-client',
        'java/', 'okhttp', 'axios/', 'guzzlehttp', 'postmanruntime',
        'whatsapp', 'telegrambot', 'discordbot', 'slackbot', 'embedly',
        'preview', 'validator', 'monitoring', 'apachebench', 'siege',
    ];

    /** @var array<string, string> needle => reported name, most specific first */
    private const BROWSERS = [
        'edg/' => 'Edge',
        'edga/' => 'Edge',
        'edgios/' => 'Edge',
        'opr/' => 'Opera',
        'opera' => 'Opera',
        'samsungbrowser' => 'Samsung Internet',
        'ucbrowser' => 'UC Browser',
        'yabrowser' => 'Yandex',
        'firefox' => 'Firefox',
        'fxios' => 'Firefox',
        'crios' => 'Chrome',
        'chrome' => 'Chrome',
        'safari' => 'Safari',
    ];

    /** @var array<string, string> needle => reported name, most specific first */
    private const PLATFORMS = [
        'windows phone' => 'Windows Phone',
        'windows' => 'Windows',
        'android' => 'Android',
        'ipad' => 'iPadOS',
        'iphone' => 'iOS',
        'ipod' => 'iOS',
        'cros' => 'ChromeOS',
        'mac os x' => 'macOS',
        'macintosh' => 'macOS',
        'linux' => 'Linux',
    ];

    public function isBot(?string $userAgent): bool
    {
        // An empty user agent is not a browser. Every real one sends
        // something; scripts frequently send nothing.
        if ($userAgent === null || trim($userAgent) === '') {
            return true;
        }

        $needle = strtolower($userAgent);

        foreach (self::BOT_SIGNATURES as $signature) {
            if (str_contains($needle, $signature)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{device_type: ?string, browser: ?string, platform: ?string}
     */
    public function parse(?string $userAgent): array
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return ['device_type' => null, 'browser' => null, 'platform' => null];
        }

        $needle = strtolower($userAgent);

        return [
            'device_type' => $this->deviceType($needle),
            'browser' => $this->firstMatch($needle, self::BROWSERS),
            'platform' => $this->firstMatch($needle, self::PLATFORMS),
        ];
    }

    /**
     * Tablet before mobile: an Android tablet's UA contains "android" but
     * NOT "mobile", which is the documented way Android distinguishes the
     * two, and an iPad says "ipad" while also matching nothing else useful.
     */
    private function deviceType(string $needle): string
    {
        if (str_contains($needle, 'ipad') || str_contains($needle, 'tablet')
            || (str_contains($needle, 'android') && ! str_contains($needle, 'mobile'))) {
            return self::DEVICE_TABLET;
        }

        if (str_contains($needle, 'mobi') || str_contains($needle, 'iphone') || str_contains($needle, 'ipod')) {
            return self::DEVICE_MOBILE;
        }

        return self::DEVICE_DESKTOP;
    }

    /**
     * @param  array<string, string>  $map
     */
    private function firstMatch(string $needle, array $map): ?string
    {
        foreach ($map as $signature => $name) {
            if (str_contains($needle, $signature)) {
                return $name;
            }
        }

        return null;
    }
}
