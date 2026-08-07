<?php

namespace Tests\Feature\Dashboard;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAdminUsers;
use Tests\TestCase;

class DashboardLocaleTest extends TestCase
{
    use CreatesAdminUsers, RefreshDatabase;

    public function test_guest_is_redirected_from_locale_toggle(): void
    {
        $this->patch(route('dashboard.locale.update'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden_from_locale_toggle(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('dashboard.locale.update'))->assertForbidden();
    }

    public function test_admin_defaults_to_arabic_locale(): void
    {
        $admin = $this->makeAdmin();

        $this->assertSame('ar', $admin->locale);
    }

    public function test_toggle_switches_and_persists_the_current_users_locale_only(): void
    {
        $admin = $this->makeAdmin();
        $otherAdmin = $this->makeAdmin();

        $this->actingAs($admin)->patch(route('dashboard.locale.update'))->assertRedirect();

        $this->assertSame('en', $admin->fresh()->locale);
        $this->assertSame('ar', $otherAdmin->fresh()->locale, 'toggling one admin must never affect another user\'s locale');

        // Toggling again flips it back — and simulating a fresh
        // request (a brand new TestCase-level request, as if the admin
        // logged out and back in) proves the choice was persisted to
        // the user record, not just held in session.
        $this->actingAs($admin->fresh())->patch(route('dashboard.locale.update'))->assertRedirect();
        $this->assertSame('ar', $admin->fresh()->locale);
    }

    public function test_english_locale_admin_sees_dashboard_chrome_in_english(): void
    {
        $admin = $this->makeAdmin();
        $admin->update(['locale' => 'en']);

        $response = $this->actingAs($admin)->get(route('dashboard.services.index'));

        $response->assertOk();
        $response->assertSee('Services');
        $response->assertSee('Add Service');
        $response->assertDontSee('الخدمات');
    }

    public function test_arabic_locale_admin_sees_dashboard_chrome_in_arabic(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard.services.index'));

        $response->assertOk();
        $response->assertSee('الخدمات');
        $response->assertSee('إضافة خدمة');
    }

    public function test_english_locale_admin_sees_english_validation_errors_not_arabic(): void
    {
        $admin = $this->makeAdmin();
        $admin->update(['locale' => 'en']);

        $response = $this->actingAs($admin)->post(route('dashboard.services.store'), []);

        $response->assertSessionHasErrors('name.ar');
        $message = session('errors')->first('name.ar');

        $this->assertDoesNotMatchRegularExpression('/\p{Arabic}/u', $message);
    }

    public function test_arabic_locale_admin_still_sees_arabic_validation_errors(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('dashboard.services.store'), []);

        $response->assertSessionHasErrors('name.ar');
        $message = session('errors')->first('name.ar');

        $this->assertMatchesRegularExpression('/\p{Arabic}/u', $message);
    }

    public function test_english_locale_admin_sees_arabic_fallback_when_english_translation_missing(): void
    {
        $admin = $this->makeAdmin();
        $admin->update(['locale' => 'en']);

        $service = Service::create([
            'slug' => 'arabic-only-service',
            'name' => ['ar' => 'خدمة بدون ترجمة إنجليزية'],
            'summary' => ['ar' => 'ملخص'],
            'body' => ['ar' => 'محتوى'],
            'requirements' => ['ar' => 'المتطلبات'],
            'process' => ['ar' => 'الخطوات'],
            'sort_order' => 1,
            'is_active' => true,
            'is_flagship' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.services.index'));

        $response->assertOk();
        $response->assertSee('خدمة بدون ترجمة إنجليزية');
        $this->assertSame('خدمة بدون ترجمة إنجليزية', $service->fresh()->name);
    }

    public function test_dashboard_locale_toggle_does_not_affect_public_site_locale(): void
    {
        $admin = $this->makeAdmin();
        $admin->update(['locale' => 'en']);

        // The admin's dashboard is now English...
        $this->actingAs($admin)->get(route('dashboard.services.index'))->assertSee('Services');

        // ...but the public Arabic site (no /en/ prefix) is completely
        // unaffected by the dashboard locale — it resolves its own
        // locale from the route name via the separate SetLocale
        // middleware.
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
    }

    public function test_coming_soon_placeholder_pages_respect_the_admins_locale(): void
    {
        // Route closures for these placeholders build their __() calls
        // once, when routes/dashboard.php loads (before any request-
        // scoped middleware runs) — this guards against that call being
        // eagerly evaluated in the boot-time locale instead of lazily
        // per-request, which would silently freeze this page in Arabic
        // regardless of the toggle.
        $admin = $this->makeAdmin();
        $admin->update(['locale' => 'en']);

        $response = $this->actingAs($admin)->get(route('dashboard.reports.index'));

        $response->assertOk();
        $response->assertSee('Reports');
        $response->assertSee('coming soon');
        $response->assertDontSee('التقارير');
    }

    public function test_dashboard_html_dir_is_rtl_for_arabic_and_ltr_for_english_admins(): void
    {
        $arabicAdmin = $this->makeAdmin();
        $englishAdmin = $this->makeAdmin();
        $englishAdmin->update(['locale' => 'en']);

        $this->actingAs($arabicAdmin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dir="rtl"', false);

        $this->actingAs($englishAdmin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dir="ltr"', false);
    }

    public function test_top_bar_controls_use_logical_end_margin_not_a_fragile_justify_between(): void
    {
        // Regression guard for a real bug: the top bar's hamburger
        // button is `lg:hidden`, so at desktop widths it is removed
        // from the box tree entirely. `justify-between` with only one
        // remaining flex child collapses to flex-start per the CSS
        // spec — meaning the admin-controls cluster landed on the
        // WRONG side (and the same physical side regardless of `dir`)
        // instead of mirroring. Fixed with `ms-auto` on the cluster,
        // which pushes it to the logical end unconditionally,
        // independent of the hamburger's visibility. This test does
        // not (and cannot, without a real browser) verify the visual
        // result — see TASKS.md for the manual verification steps.
        $admin = $this->makeAdmin();

        $html = $this->actingAs($admin)->get(route('dashboard'))->getContent();

        $this->assertStringContainsString('ms-auto flex items-center gap-4', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/<header[^>]*justify-between[^>]*>/',
            $html,
            'the top bar <header> must not rely on justify-between — it silently breaks when its first child is conditionally hidden'
        );
    }

    public function test_sidebar_closed_state_transform_is_scoped_below_the_lg_breakpoint(): void
    {
        // Regression guard for a real bug introduced by the RTL/LTR
        // mirroring fix: the sidebar's Alpine `:class` binding set
        // `rtl:translate-x-full` / `ltr:-translate-x-full` with NO
        // breakpoint qualification, while the static `class` attribute
        // separately carries `lg:translate-x-0` to keep the sidebar
        // permanently visible at desktop widths. Both rules have equal
        // CSS specificity (Tailwind wraps `[dir=rtl]` in `:where()`,
        // which contributes zero specificity), so the browser fell
        // back to cascade order — and the unconditional `rtl:`/`ltr:`
        // rule was emitted AFTER the media-query-scoped `lg:` rule in
        // Tailwind's compiled output, so it won even at desktop
        // widths. Confirmed empirically by inspecting the compiled CSS
        // (`.rtl\:translate-x-full` had no `@media` wrapper at all,
        // while `.lg\:translate-x-0` was inside
        // `@media (min-width:1024px)`). Since the app defaults to the
        // `ar` locale (`dir="rtl"`), this hid the sidebar on every
        // desktop page load.
        //
        // Fixed by scoping the closed-state classes to `max-lg:`, so
        // they compile into `@media not all and (min-width:1024px)` —
        // mutually exclusive with `lg:`'s `@media (min-width:1024px)`,
        // removing the cascade-order ambiguity entirely rather than
        // relying on source-order to break the tie.
        $admin = $this->makeAdmin();

        $html = $this->actingAs($admin)->get(route('dashboard'))->getContent();

        $this->assertMatchesRegularExpression(
            '/:class="sidebarOpen \? \'translate-x-0\' : \'max-lg:rtl:translate-x-full max-lg:ltr:-translate-x-full\'"/',
            $html,
            'the sidebar\'s closed-state RTL/LTR transform must be scoped to max-lg: so it can never conflict with the lg:translate-x-0 desktop override'
        );
        $this->assertStringContainsString('lg:translate-x-0', $html);
    }

    public function test_create_and_edit_forms_always_show_both_language_inputs_regardless_of_toggle(): void
    {
        $admin = $this->makeAdmin();
        $admin->update(['locale' => 'en']);

        $response = $this->actingAs($admin)->get(route('dashboard.services.create'));

        $response->assertOk();
        $response->assertSee('name[ar]', false);
        $response->assertSee('name[en]', false);
    }
}
