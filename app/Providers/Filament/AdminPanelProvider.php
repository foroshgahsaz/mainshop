<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ManageTara;
use App\Http\Middleware\SetPersianLocale;
use Filament\FontProviders\LocalFontProvider;
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
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->topbar(false)
            ->brandName('چاپینو')
            ->font('YekanBakh', provider: LocalFontProvider::class)
            ->favicon(asset('shop/images/categories/code.svg'))
            ->colors([
                'primary' => Color::hex('#7239ea'),
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Green,
                'info' => Color::Sky,
            ])
            ->sidebarCollapsibleOnDesktop(false)
            ->navigationGroups([
                NavigationGroup::make('فروشگاه')->icon('heroicon-o-shopping-bag'),
                NavigationGroup::make('کاربران')->icon('heroicon-o-users'),
                NavigationGroup::make('محتوا')->icon('heroicon-o-document-text'),
                NavigationGroup::make('تنظیمات')->icon('heroicon-o-cog-6-tooth'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                ManageTara::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.hooks.styles'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.hooks.scripts'))
            ->renderHook(PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER, fn () => view('filament.partials.global-header-actions'))
            ->middleware([
                SetPersianLocale::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
