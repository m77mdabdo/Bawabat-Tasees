<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\UserAgentParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserAgentParserTest extends TestCase
{
    private UserAgentParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new UserAgentParser;
    }

    // -----------------------------------------------------------------
    // Bot filtering
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function bots(): array
    {
        return [
            'googlebot' => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'bingbot' => ['Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'],
            'yandex' => ['Mozilla/5.0 (compatible; YandexBot/3.0)'],
            'baidu spider' => ['Mozilla/5.0 (compatible; Baiduspider/2.0)'],
            'yahoo slurp' => ['Mozilla/5.0 (compatible; Yahoo! Slurp)'],
            'ahrefs' => ['Mozilla/5.0 (compatible; AhrefsBot/7.0)'],
            'semrush' => ['Mozilla/5.0 (compatible; SemrushBot/7~bl)'],
            'facebook link preview' => ['facebookexternalhit/1.1'],
            'whatsapp link preview' => ['WhatsApp/2.19.81 A'],
            'telegram' => ['TelegramBot (like TwitterBot)'],
            'slack unfurl' => ['Slackbot-LinkExpanding 1.0'],
            'curl' => ['curl/8.4.0'],
            'wget' => ['Wget/1.21.3'],
            'python requests' => ['python-requests/2.31.0'],
            'go http client' => ['Go-http-client/2.0'],
            'postman' => ['PostmanRuntime/7.36.0'],
            'headless chrome' => ['Mozilla/5.0 (X11; Linux x86_64) HeadlessChrome/120.0.0.0'],
            'puppeteer' => ['Mozilla/5.0 Puppeteer'],
            'lighthouse' => ['Mozilla/5.0 Chrome-Lighthouse'],
            'uptime monitor' => ['Mozilla/5.0 (compatible; UptimeRobot/2.0)'],
        ];
    }

    #[DataProvider('bots')]
    public function test_automated_traffic_is_detected(string $userAgent): void
    {
        $this->assertTrue($this->parser->isBot($userAgent), "Not flagged as a bot: {$userAgent}");
    }

    /**
     * @return array<string, array{string}>
     */
    public static function humans(): array
    {
        return [
            'chrome on windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'],
            'safari on iphone' => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1'],
            'firefox on linux' => ['Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0'],
            'edge on windows' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0'],
            'samsung internet' => ['Mozilla/5.0 (Linux; Android 13; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36'],
            'safari on ipad' => ['Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/604.1'],
        ];
    }

    #[DataProvider('humans')]
    public function test_real_browsers_are_not_flagged(string $userAgent): void
    {
        $this->assertFalse($this->parser->isBot($userAgent), "Wrongly flagged as a bot: {$userAgent}");
    }

    /**
     * Every real browser sends a user agent. Scripts frequently send none,
     * so an empty one is treated as automated rather than as a mystery
     * human.
     */
    public function test_a_missing_user_agent_counts_as_a_bot(): void
    {
        $this->assertTrue($this->parser->isBot(null));
        $this->assertTrue($this->parser->isBot(''));
        $this->assertTrue($this->parser->isBot('   '));
    }

    public function test_detection_is_case_insensitive(): void
    {
        $this->assertTrue($this->parser->isBot('GOOGLEBOT/2.1'));
        $this->assertTrue($this->parser->isBot('CURL/8.4.0'));
    }

    // -----------------------------------------------------------------
    // Parsing
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function agents(): array
    {
        return [
            'chrome on windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                UserAgentParser::DEVICE_DESKTOP, 'Chrome', 'Windows',
            ],
            // Edge carries "Chrome" AND "Safari" in its UA; the order of the
            // browser list is the only reason this does not report Chrome.
            'edge on windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
                UserAgentParser::DEVICE_DESKTOP, 'Edge', 'Windows',
            ],
            'opera on windows' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36 OPR/105.0.0.0',
                UserAgentParser::DEVICE_DESKTOP, 'Opera', 'Windows',
            ],
            'safari on iphone' => [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
                UserAgentParser::DEVICE_MOBILE, 'Safari', 'iOS',
            ],
            // iPadOS reports itself as Macintosh; the iPad check has to come
            // first or this would be a macOS desktop.
            'safari on ipad' => [
                'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/604.1',
                UserAgentParser::DEVICE_TABLET, 'Safari', 'iPadOS',
            ],
            'chrome on android phone' => [
                'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
                UserAgentParser::DEVICE_MOBILE, 'Chrome', 'Android',
            ],
            // No "Mobile" token: that is how Android marks a tablet.
            'chrome on android tablet' => [
                'Mozilla/5.0 (Linux; Android 13; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                UserAgentParser::DEVICE_TABLET, 'Chrome', 'Android',
            ],
            'firefox on linux' => [
                'Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0',
                UserAgentParser::DEVICE_DESKTOP, 'Firefox', 'Linux',
            ],
            'safari on macos' => [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
                UserAgentParser::DEVICE_DESKTOP, 'Safari', 'macOS',
            ],
        ];
    }

    #[DataProvider('agents')]
    public function test_device_browser_and_platform_are_parsed(string $ua, string $device, string $browser, string $platform): void
    {
        $parsed = $this->parser->parse($ua);

        $this->assertSame($device, $parsed['device_type']);
        $this->assertSame($browser, $parsed['browser']);
        $this->assertSame($platform, $parsed['platform']);
    }

    /**
     * An unknown browser reports as null rather than being guessed at. The
     * dashboard shows a dash; nothing branches on it.
     */
    public function test_an_unrecognised_agent_yields_nulls_not_guesses(): void
    {
        $parsed = $this->parser->parse('SomeEntirelyNewBrowser/1.0');

        $this->assertNull($parsed['browser']);
        $this->assertNull($parsed['platform']);
        // Device still falls back to desktop, which is the safe default for
        // something with no mobile marker at all.
        $this->assertSame(UserAgentParser::DEVICE_DESKTOP, $parsed['device_type']);
    }

    public function test_an_empty_agent_parses_to_nothing(): void
    {
        $this->assertSame(
            ['device_type' => null, 'browser' => null, 'platform' => null],
            $this->parser->parse(null)
        );
    }
}
