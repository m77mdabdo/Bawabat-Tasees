<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Presentation-only coverage for the redesigned auth card. Auth logic is
 * covered by the existing Auth tests — these assert the five screens keep
 * a single, consistent visual language and never regress to the Breeze
 * indigo defaults.
 */
class AuthCardPresentationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function authPages(): array
    {
        return [
            'login' => ['/login'],
            'forgot password' => ['/forgot-password'],
            'confirm password' => ['__confirm__'],
            'reset password' => ['__reset__'],
            'verify email' => ['__verify__'],
        ];
    }

    /**
     * The last three need either a token or an authenticated user, so the
     * provider passes a marker the test resolves here.
     */
    private function visit(string $page): TestResponse
    {
        return match ($page) {
            '__confirm__' => $this->actingAs(User::factory()->create())->get('/confirm-password'),
            '__reset__' => $this->get('/reset-password/'.Password::createToken(User::factory()->create()).'?email=a@b.test'),
            '__verify__' => $this->actingAs(User::factory()->unverified()->create())->get('/verify-email'),
            default => $this->get($page),
        };
    }

    #[DataProvider('authPages')]
    public function test_page_renders_the_shared_card(string $page): void
    {
        $html = $this->visit($page)->assertOk()->getContent();

        // The frosted card shell, brand logo and site name.
        $this->assertStringContainsString('bg-white/95', $html);
        $this->assertStringContainsString('backdrop-blur-xl', $html);
        $this->assertStringContainsString('logo-icon-color-128.png', $html);
        $this->assertStringContainsString(__('site.brand.name'), $html);
    }

    #[DataProvider('authPages')]
    public function test_page_keeps_the_video_background(string $page): void
    {
        $html = $this->visit($page)->assertOk()->getContent();

        $this->assertStringContainsString('videos/login-bg.webm', $html);
        $this->assertStringContainsString('videos/login-bg.mp4', $html);
        $this->assertStringContainsString('images/login-poster.jpg', $html);
    }

    #[DataProvider('authPages')]
    public function test_page_has_no_leftover_breeze_indigo(string $page): void
    {
        $html = $this->visit($page)->assertOk()->getContent();

        $this->assertStringNotContainsString('indigo-', $html);
    }

    #[DataProvider('authPages')]
    public function test_page_uses_the_brand_primary_button(string $page): void
    {
        $html = $this->visit($page)->assertOk()->getContent();

        $this->assertStringContainsString('bg-primary-green', $html);
    }

    public function test_password_fields_have_a_show_hide_toggle(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('x-data="{ show: false }"', $html);
        $this->assertStringContainsString('show = ! show', $html);
        $this->assertStringContainsString(__('dashboard.auth.show_password'), $html);
        $this->assertStringContainsString(__('dashboard.auth.hide_password'), $html);
    }

    public function test_email_and_password_inputs_have_leading_icons_and_real_labels(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('<label for="email"', $html);
        $this->assertStringContainsString('<label for="password"', $html);
        // Logical-property padding so the icon sits on the leading edge
        // under both RTL and LTR.
        $this->assertStringContainsString('ps-11', $html);
    }

    public function test_validation_errors_are_styled_and_flagged_on_the_input(): void
    {
        // from('/login') matters: the failed POST redirects back(), which
        // without a referer lands on "/" and would assert nothing.
        $html = $this->from('/login')
            ->followingRedirects()
            ->post('/login', ['email' => 'not-an-email', 'password' => ''])
            ->assertOk()
            ->getContent();

        // The message itself, plus the input's own error affordances.
        $this->assertStringContainsString('text-red-600', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('border-red-400', $html);
    }

    public function test_a_clean_form_has_no_error_styling(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('aria-invalid="true"', $html);
        $this->assertStringNotContainsString('border-red-400', $html);
    }

    public function test_login_renders_in_both_locales(): void
    {
        app()->setLocale('ar');
        $ar = $this->get('/login')->assertOk()->getContent();
        $this->assertStringContainsString(__('dashboard.auth.email', [], 'ar'), $ar);
        $this->assertStringContainsString('dir="rtl"', $ar);

        app()->setLocale('en');
        $en = $this->get('/login')->assertOk()->getContent();
        $this->assertStringContainsString(__('dashboard.auth.email', [], 'en'), $en);
        $this->assertStringContainsString('dir="ltr"', $en);
    }
}
