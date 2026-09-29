<?php

namespace App\Providers\Filament;

use App\Filament\Representative\Pages\Auth\Login;
use App\Filament\Representative\Pages\Dashboard;
use App\Http\Middleware\SetPersianLocale;
use App\Services\Settings\SettingsService;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
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

class RepresentativePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('representative')
            ->path('representative')
            ->login(Login::class)
            ->brandName(fn () => (app(SettingsService::class)->site()['name'] ?? 'فروشگاه').' — پنل نمایندگی')
            ->font('YekanBakh', provider: LocalFontProvider::class)
            ->favicon(asset('shop/images/categories/code.svg'))
            ->colors([
                'primary' => Color::hex('#0d9488'),
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Representative/Resources'), for: 'App\\Filament\\Representative\\Resources')
            ->discoverPages(in: app_path('Filament/Representative/Pages'), for: 'App\\Filament\\Representative\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('filament.hooks.styles'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.hooks.scripts'))
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
