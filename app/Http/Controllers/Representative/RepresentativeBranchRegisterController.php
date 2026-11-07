<?php

namespace App\Http\Controllers\Representative;

use App\Http\Controllers\Controller;
use App\Support\ShopMedia;
use App\Services\Settings\SettingsService;
use Illuminate\View\View;

/**
 * صفحهٔ عمومی درخواست نمایندگی (فعلاً استاتیک — بدون ادمین و ذخیرهٔ فرم).
 */
class RepresentativeBranchRegisterController extends Controller
{
    public function __invoke(): View
    {
        $site = app(SettingsService::class)->site();
        $siteName = $site['name'] ?? config('app.name', 'چینی بازار');
        $logo = $site['logo'] ?? null;
        $ogImage = is_string($logo) && $logo !== '' ? ShopMedia::url($logo) : null;

        $contactPhone = $site['phone'] ?? $site['mobile'] ?? null;

        $tehranOffice = [
            'title' => 'دفتر تهران',
            'leasing_badge' => '#1',
            'leasing_label' => 'چینی بازار',
            'manager' => [
                'name' => 'تیم نمایندگی چینی بازار',
                'phone' => is_string($contactPhone) && $contactPhone !== '' ? $contactPhone : '—',
            ],
            'specialists' => [
                ['name' => 'کارشناس نمایندگی', 'phone' => is_string($contactPhone) && $contactPhone !== '' ? $contactPhone : '—'],
            ],
            'provinces' => ['تهران'],
        ];

        return view('representative.branch-register', [
            'siteName' => $siteName,
            'seoImage' => $ogImage,
            'tehranOffice' => $tehranOffice,
            'regionsMeta' => [
                'region_count' => 1,
                'updated_label' => now()->timezone('Asia/Tehran')->format('Y-m-d H:i'),
            ],
        ]);
    }
}
