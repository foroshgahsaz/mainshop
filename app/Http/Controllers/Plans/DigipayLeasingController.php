<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Support\ShopMedia;
use App\Services\Settings\SettingsService;
use Illuminate\View\View;

class DigipayLeasingController extends Controller
{
    /** سقف اعتبار نمایشی (میلیون تومان) */
    public const CREDIT_CAP_MILLIONS = 200;

    public function __invoke(): View
    {
        $site = app(SettingsService::class)->site();
        $logo = $site['logo'] ?? null;
        $ogImage = is_string($logo) && $logo !== '' ? ShopMedia::url($logo) : null;

        return view('plans.digipay-leasing', [
            'creditCapMillions' => self::CREDIT_CAP_MILLIONS,
            'seoImage' => $ogImage,
            'siteName' => $site['name'] ?? config('app.name', 'چینی بازار'),
        ]);
    }
}
