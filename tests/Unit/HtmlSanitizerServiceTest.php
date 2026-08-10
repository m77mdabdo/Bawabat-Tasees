<?php

namespace Tests\Unit;

use App\Services\Cms\HtmlSanitizerService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The XSS boundary for the entire project.
 *
 * Six views render raw HTML with {!! !!} — every Page body and every Article
 * body. Nothing else in the codebase is unescaped. Both of those fields go
 * through this service on write (ArticleController, PageController), so
 * whatever it lets through is, by definition, what a visitor's browser will
 * execute.
 *
 * It had no direct test until now; it was exercised only side-on through
 * controller feature tests, which assert that saving works rather than that
 * attacks fail.
 */
class HtmlSanitizerServiceTest extends TestCase
{
    private HtmlSanitizerService $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = new HtmlSanitizerService;
    }

    // -----------------------------------------------------------------
    // sanitizeArticleBody — the allow-list holds
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string}>
     */
    public static function allowedMarkup(): array
    {
        return [
            'paragraph' => ['<p>Hello</p>'],
            'h2' => ['<h2>Heading</h2>'],
            'h3' => ['<h3>Heading</h3>'],
            'h4' => ['<h4>Heading</h4>'],
            'unordered list' => ['<ul><li>One</li></ul>'],
            'ordered list' => ['<ol><li>One</li></ol>'],
            'strong' => ['<p><strong>Bold</strong></p>'],
            'emphasis' => ['<p><em>Italic</em></p>'],
            'blockquote' => ['<blockquote>Quoted</blockquote>'],
        ];
    }

    #[DataProvider('allowedMarkup')]
    public function test_allowed_markup_survives(string $html): void
    {
        $clean = $this->sanitizer->sanitizeArticleBody($html);

        $tag = preg_replace('/^<(\w+).*/s', '$1', $html);

        $this->assertStringContainsString('<'.$tag, $clean, "The allow-list dropped <{$tag}>, which the editor hint promises works.");
    }

    public function test_a_link_keeps_its_href(): void
    {
        $clean = $this->sanitizer->sanitizeArticleBody('<p><a href="https://example.com">Link</a></p>');

        $this->assertStringContainsString('href="https://example.com"', $clean);
    }

    public function test_an_image_keeps_its_src_and_alt(): void
    {
        $clean = $this->sanitizer->sanitizeArticleBody('<p><img src="https://example.com/a.png" alt="Alt text"></p>');

        $this->assertStringContainsString('src="https://example.com/a.png"', $clean);
        $this->assertStringContainsString('alt="Alt text"', $clean);
    }

    // -----------------------------------------------------------------
    // sanitizeArticleBody — the attacks do not
    // -----------------------------------------------------------------

    /**
     * @return array<string, array{string, string}>
     */
    public static function attacks(): array
    {
        return [
            'script tag' => ['<p>Hi</p><script>alert(1)</script>', 'alert(1)'],
            'inline onerror' => ['<img src="x" onerror="alert(1)">', 'onerror'],
            'inline onclick' => ['<p onclick="alert(1)">Hi</p>', 'onclick'],
            'javascript: href' => ['<a href="javascript:alert(1)">Click</a>', 'javascript:'],
            'data: uri href' => ['<a href="data:text/html,<script>alert(1)</script>">Click</a>', 'alert(1)'],
            'iframe' => ['<iframe src="https://evil.test"></iframe>', '<iframe'],
            'object' => ['<object data="https://evil.test"></object>', '<object'],
            'embed' => ['<embed src="https://evil.test">', '<embed'],
            'form' => ['<form action="https://evil.test"><input name="p"></form>', '<form'],
            'style tag' => ['<style>body{display:none}</style>', 'display:none'],
            'style attribute' => ['<p style="position:fixed;inset:0">Hi</p>', 'position:fixed'],
            'svg with script' => ['<svg><script>alert(1)</script></svg>', 'alert(1)'],
            'base tag' => ['<base href="https://evil.test/">', '<base'],
            'meta refresh' => ['<meta http-equiv="refresh" content="0;url=https://evil.test">', 'http-equiv'],
            'uppercase script' => ['<SCRIPT>alert(1)</SCRIPT>', 'alert(1)'],
            // The mangled-tag trick: if the sanitiser removed "<script>" by
            // simple replacement, the surrounding fragments would close up
            // into a working tag. It must not reconstruct one — see
            // test_a_mangled_script_tag_survives_only_as_escaped_text for
            // what it is allowed to leave behind.
            'nested broken script' => ['<scr<script>ipt>alert(1)</script>', '<script'],
        ];
    }

    #[DataProvider('attacks')]
    public function test_dangerous_markup_is_stripped(string $html, string $mustNotSurvive): void
    {
        $clean = $this->sanitizer->sanitizeArticleBody($html);

        $this->assertStringNotContainsString($mustNotSurvive, $clean);
        $this->assertStringNotContainsString('<script', strtolower($clean));
    }

    /**
     * The one case where the literal string "alert(1)" is allowed to remain:
     * as ESCAPED text. A mangled tag leaves orphan characters behind, and
     * HTMLPurifier entity-encodes them rather than reassembling a tag — so
     * the page displays "ipt&gt;alert(1)" as words. That is the correct
     * outcome; the security property is that nothing executes, not that a
     * particular substring is absent.
     */
    public function test_a_mangled_script_tag_survives_only_as_escaped_text(): void
    {
        $clean = $this->sanitizer->sanitizeArticleBody('<scr<script>ipt>alert(1)</script>');

        $this->assertStringNotContainsString('<script', strtolower($clean));
        $this->assertStringNotContainsString('<scr', strtolower($clean));
        // Whatever is left is inert text, not markup.
        $this->assertSame(strip_tags($clean), $clean);
    }

    /**
     * Stripping a <script> must take its BODY with it, not leave the
     * JavaScript behind as visible page text — the specific difference
     * between an allow-list sanitiser and PHP's strip_tags().
     */
    public function test_a_stripped_script_leaves_no_visible_source_code(): void
    {
        $clean = $this->sanitizer->sanitizeArticleBody('<p>Before</p><script>var secret = 42;</script><p>After</p>');

        $this->assertStringContainsString('Before', $clean);
        $this->assertStringContainsString('After', $clean);
        $this->assertStringNotContainsString('secret', $clean);
    }

    public function test_an_empty_body_stays_empty(): void
    {
        $this->assertSame('', $this->sanitizer->sanitizeArticleBody(''));
    }

    /**
     * Arabic is the primary authoring language, so a sanitiser that mangled
     * multi-byte text would break the main content path rather than an edge
     * case.
     */
    public function test_arabic_content_is_preserved_intact(): void
    {
        $arabic = '<p>تأسيس الشركات في المملكة العربية السعودية</p>';

        $this->assertStringContainsString(
            'تأسيس الشركات في المملكة العربية السعودية',
            $this->sanitizer->sanitizeArticleBody($arabic)
        );
    }

    public function test_sanitising_twice_changes_nothing_further(): void
    {
        $once = $this->sanitizer->sanitizeArticleBody('<p>Hello <strong>world</strong></p><script>alert(1)</script>');
        $twice = $this->sanitizer->sanitizeArticleBody($once);

        $this->assertSame($once, $twice);
    }

    // -----------------------------------------------------------------
    // stripAllTags — titles and excerpts, which must stay plain text
    // -----------------------------------------------------------------

    public function test_strip_all_tags_removes_every_tag(): void
    {
        $this->assertSame('Hello world', $this->sanitizer->stripAllTags('<p>Hello <strong>world</strong></p>'));
    }

    /**
     * The reason this uses HTMLPurifier's empty allow-list rather than
     * strip_tags(): strip_tags('<script>alert(1)</script>') returns
     * "alert(1)", which would then be printed into a page title.
     */
    public function test_strip_all_tags_removes_script_bodies_not_just_the_tags(): void
    {
        $this->assertStringNotContainsString('alert(1)', $this->sanitizer->stripAllTags('<script>alert(1)</script>'));
        $this->assertStringNotContainsString('display:none', $this->sanitizer->stripAllTags('<style>body{display:none}</style>'));
    }

    public function test_strip_all_tags_trims_its_result(): void
    {
        $this->assertSame('Title', $this->sanitizer->stripAllTags('   <p>Title</p>   '));
    }

    public function test_strip_all_tags_keeps_arabic_text(): void
    {
        $this->assertSame('خدماتنا', $this->sanitizer->stripAllTags('<h2>خدماتنا</h2>'));
    }

    public function test_strip_all_tags_on_plain_text_is_a_no_op(): void
    {
        $this->assertSame('Just a title', $this->sanitizer->stripAllTags('Just a title'));
    }
}
