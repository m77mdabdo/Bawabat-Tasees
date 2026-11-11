<?php

namespace App\Providers\Filament;

use App\Auth\EmailSignInCode;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetDashboardLocale;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The staff dashboard at /dashboard.
 *
 * Access is decided by User::canAccessPanel (active account + a role with dashboard.access);
 * what each screen allows is decided by the permission names in App\Auth\PermissionMatrix.
 * Every sign-in is finished with a one-time code emailed to the person signing in.
 */
class DashboardPanelProvider extends PanelProvider
{
    /**
     * The brand green at shade 600 (buttons, active items) with lighter and darker steps around it.
     * Color::hex() keeps only the hue, which gives a much lighter generic green.
     *
     * @return array<int, string>
     */
    private static function brandGreen(): array
    {
        return array_map(fn (string $hex) => Color::convertToOklch($hex), [
            50 => '#eef7f2', 100 => '#d5ecdf', 200 => '#acd9c0', 300 => '#78bf99', 400 => '#3f9c6d',
            500 => '#167a4c', 600 => '#0B5D3B', 700 => '#094f32', 800 => '#073E2A', 900 => '#063322', 950 => '#031d13',
        ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->multiFactorAuthentication([EmailSignInCode::make()], isRequired: true)
            ->brandName(fn () => __('mail.brand'))
            ->brandLogo(asset('images/brand/logo-full-color-256.png'))
            ->darkModeBrandLogo(asset('images/brand/logo-full-white-256.png'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/brand/favicon.ico'))
            ->font('IBM Plex Sans Arabic')
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => self::brandGreen(),
                'warning' => Color::hex('#B8903A'),
                'gray' => Color::Stone,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->navigationGroups([
                NavigationGroup::make('enquiries')->label(fn () => __('Enquiries')),
                NavigationGroup::make('content')->label(fn () => __('Website content')),
                NavigationGroup::make('marketing')->label(fn () => __('Marketing & analytics')),
                NavigationGroup::make('settings')->label(fn () => __('Settings')),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render('filament.theme'))
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => Blade::render('filament.locale-switch'))
            ->renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE, fn () => Blade::render('filament.view-site'))
            ->databaseNotifications()
            ->databaseNotificationsPolling('5s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                SecurityHeaders::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetDashboardLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ThrottleRequests::using('dashboard-writes'),
            ]);
    }
}
