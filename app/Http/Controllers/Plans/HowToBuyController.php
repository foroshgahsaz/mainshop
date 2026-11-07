<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;
use App\Support\ShopMedia;
use Illuminate\View\View;

/**
 * راهنمای خرید اعتباری/اقساطی از چینی بازار (صفحهٔ استاتیک — شبیه aloghesti.com/how-to-buy).
 */
class HowToBuyController extends Controller
{
    public function __invoke(PaymentGatewayCatalog $gateways): View
    {
        $site = app(SettingsService::class)->site();
        $siteName = $site['name'] ?? config('app.name', 'چینی بازار');
        $logo = $site['logo'] ?? null;
        $ogImage = is_string($logo) && $logo !== '' ? ShopMedia::url($logo) : null;

        $icon = fn (string $name): ?string => $gateways->definition($name)['icon'] ?? null;

        $planRows = [
            [
                'id' => 'tejarat-bnpl',
                'title' => 'خرید اعتباری بانک تجارت',
                'lead' => 'طرح‌های مرتبط با تجارت و خرید الان، پرداخت بعداً',
                'plans' => [
                    [
                        'label' => 'BNPL بانک تجارت',
                        'description' => 'خرید اعتباری با طرح BNPL بانک تجارت — در checkout چینی بازار انتخاب می‌شود.',
                        'icon' => null,
                        'cta' => null,
                    ],
                    [
                        'label' => 'کالاپی',
                        'description' => 'اعتبار خرید کالا از طریق کالاپی؛ پس از انتخاب کالا در checkout درگاه را بزنید.',
                        'icon' => null,
                        'cta' => null,
                    ],
                    [
                        'label' => $gateways->definition('nopay')['label'] ?: 'نوپی',
                        'description' => 'پرداخت اعتباری نوپی (CPG) برای سبد خرید چینی بازار.',
                        'icon' => $icon('nopay'),
                        'cta' => route('products.index'),
                    ],
                ],
            ],
            [
                'id' => 'refah-beta',
                'title' => 'بتا — بانک رفاه',
                'lead' => 'اعتبار بانک رفاه از طریق باجت‌پی',
                'plans' => [
                    [
                        'label' => 'بتا (باجت‌پی)',
                        'description' => 'پرداخت اعتباری باجت‌پی — مرتبط با طرح‌های بانک رفاه؛ در مرحلهٔ پرداخت فعال می‌شود.',
                        'icon' => $icon('bajet'),
                        'cta' => route('products.index'),
                    ],
                ],
            ],
            [
                'id' => 'wallet-credit',
                'title' => 'کیف پول و اعتبار دیجیتال',
                'lead' => 'تارا، دیجی‌پی و دیبا کارت',
                'plans' => [
                    [
                        'label' => $gateways->definition('tara')['label'] ?: 'تارا',
                        'description' => 'پرداخت از کیف پول و اعتبار تارا.',
                        'icon' => $icon('tara'),
                        'cta' => route('products.index'),
                    ],
                    [
                        'label' => $gateways->definition('digipay')['label'] ?: 'دیجی‌پی',
                        'description' => 'خرید اقساطی دیجی‌پی از چینی بازار.',
                        'icon' => $icon('digipay'),
                        'cta' => route('plans.digipay-leasing'),
                    ],
                    [
                        'label' => 'دیبا کارت',
                        'description' => 'پرداخت با دیبا کارت در checkout (در صورت فعال بودن درگاه).',
                        'icon' => null,
                        'cta' => route('products.index'),
                    ],
                ],
            ],
        ];

        return view('plans.how-to-buy', [
            'siteName' => $siteName,
            'seoImage' => $ogImage,
            'planRows' => $planRows,
        ]);
    }
}
