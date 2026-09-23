<?php

namespace App\Http\Middleware;

use App\Services\Settings\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopNotInMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $settings = app(SettingsService::class);

        if (! $settings->isMaintenanceMode()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user?->isAdmin()) {
            return $next($request);
        }

        $site = $settings->site();

        return response()->view('shop.maintenance', [
            'site' => $site,
            'message' => $site['maintenance_message'],
        ], 503);
    }

    protected function shouldBypass(Request $request): bool
    {
        return $request->is('admin', 'admin/*', 'up', 'payment/callback', 'payment/callback/*');
    }
}
