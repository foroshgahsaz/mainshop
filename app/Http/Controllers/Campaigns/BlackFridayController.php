<?php

namespace App\Http\Controllers\Campaigns;

use App\Http\Controllers\Controller;
use App\Services\Settings\BlackFridaySettingsService;
use App\Services\Settings\SettingsService;
use App\Support\ShopMedia;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BlackFridayController extends Controller
{
    public function __invoke(
        BlackFridaySettingsService $blackFriday,
        SettingsService $settings,
    ): View|Response {
        if (! $blackFriday->isPublished()) {
            abort(404);
        }

        $data = $blackFriday->all();
        $siteName = $settings->site()['name'] ?? config('app.name', 'چینی بازار');

        return view('campaigns.blackfriday', [
            'siteName' => $siteName,
            'page' => $data,
            'headerLogoUrl' => ShopMedia::url($data['header_logo'] ?? null),
            'heroLogoUrl' => ShopMedia::url($data['hero_logo'] ?? null),
            'promos' => collect($data['promos'] ?? [])
                ->map(function (array $promo): array {
                    return [
                        'label' => $promo['label'],
                        'url' => $promo['url'],
                        'image_url' => ShopMedia::url($promo['image'] ?? null),
                    ];
                })
                ->filter(fn (array $promo): bool => filled($promo['image_url']))
                ->values()
                ->all(),
        ]);
    }
}