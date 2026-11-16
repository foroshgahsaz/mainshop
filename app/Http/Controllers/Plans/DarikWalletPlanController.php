<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Services\Settings\SettingsService;
use App\Support\ShopMedia;
use Illuminate\View\View;

class DarikWalletPlanController extends Controller
{
    public function __invoke(): View
    {
        $site = app(SettingsService::class)->site();
        $logo = $site['logo'] ?? null;
        $ogImage = is_string($logo) && $logo !== '' ? ShopMedia::url($logo) : null;

        return view('plans.darik', [
            'seoImage' => $ogImage,
            'siteName' => $site['name'] ?? config('app.name', 'چینی بازار'),
        ]);
    }
}
