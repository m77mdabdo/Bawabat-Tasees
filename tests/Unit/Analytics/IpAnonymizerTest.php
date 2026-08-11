<?php

namespace Tests\Unit\Analytics;

use App\Services\Analytics\IpAnonymizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The privacy guarantee the whole feature rests on: a raw IP is never
 * stored. This class is the only thing standing between the request and the
 * column, so it is tested directly rather than through the capture path.
 */
class IpAnonymizerTest extends TestCase
{
    private IpAnonymizer $anonymizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->anonymizer = new IpAnonymizer;
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function ipv4Addresses(): array
    {
        return [
            'documentation range' => ['203.0.113.42', '203.0.113.0'],
            'private range' => ['192.168.1.77', '192.168.1.0'],
            'already truncated' => ['10.0.0.0', '10.0.0.0'],
            'last octet 255' => ['8.8.8.255', '8.8.8.0'],
            'localhost' => ['127.0.0.1', '127.0.0.0'],
        ];
    }

    #[DataProvider('ipv4Addresses')]
    public function test_ipv4_loses_its_last_octet(string $ip, string $expected): void
    {
        $this->assertSame($expected, $this->anonymizer->anonymize($ip));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function ipv6Addresses(): array
    {
        return [
            'full form' => ['2001:0db8:0000:0000:0000:ff00:0042:8329', '2001:db8::'],
            'compressed form of the same address' => ['2001:db8::ff00:42:8329', '2001:db8::'],
            'distinct /48' => ['2a00:1450:4001:81b::200e', '2a00:1450:4001::'],
            'loopback' => ['::1', '::'],
        ];
    }

    #[DataProvider('ipv6Addresses')]
    public function test_ipv6_keeps_only_its_first_48_bits(string $ip, string $expected): void
    {
        $this->assertSame($expected, $this->anonymizer->anonymize($ip));
    }

    /**
     * The two notations for one address must not become two different
     * stored values, or the same visitor would look like two.
     */
    public function test_ipv6_notation_does_not_change_the_result(): void
    {
        $this->assertSame(
            $this->anonymizer->anonymize('2001:0db8:0000:0000:0000:ff00:0042:8329'),
            $this->anonymizer->anonymize('2001:db8::ff00:42:8329')
        );
    }

    /**
     * The point of truncation over hashing: two addresses in the same /24
     * collapse to one value, so the stored prefix genuinely cannot identify
     * which of them it was.
     */
    public function test_addresses_in_the_same_block_become_indistinguishable(): void
    {
        $this->assertSame(
            $this->anonymizer->anonymize('203.0.113.1'),
            $this->anonymizer->anonymize('203.0.113.254')
        );
    }

    public function test_different_blocks_stay_distinguishable(): void
    {
        $this->assertNotSame(
            $this->anonymizer->anonymize('203.0.113.1'),
            $this->anonymizer->anonymize('203.0.114.1')
        );
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function unusableValues(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'whitespace' => ['   '],
            'the literal unknown some proxies send' => ['unknown'],
            'a hostname' => ['example.test'],
            'a spoofed header with two addresses' => ['203.0.113.1, 198.51.100.2'],
            'sql-ish junk' => ["203.0.113.1'; DROP TABLE visitor_sessions;--"],
        ];
    }

    #[DataProvider('unusableValues')]
    public function test_anything_that_is_not_an_address_is_dropped(?string $value): void
    {
        $this->assertNull(
            $this->anonymizer->anonymize($value),
            'Storing an unparsed value would defeat the point of this class.'
        );
    }

    /**
     * The guarantee stated as a property rather than a case: whatever goes
     * in, the full original must not come out.
     */
    public function test_the_original_address_is_never_returned(): void
    {
        foreach (['203.0.113.42', '2001:db8::ff00:42:8329', '192.168.1.77'] as $ip) {
            $this->assertNotSame($ip, $this->anonymizer->anonymize($ip));
        }
    }
}
