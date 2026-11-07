<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;
use App\Support\ShopMedia;
use Illuminate\View\View;

/**
 * راهنمای خرید اعتباری/اقساطی از چینی بازار — جدول مقایسهٔ طرح‌ها (استاتیک).
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

        $homeCategories = 'لوازم خانگی برقی، یخچال، لباسشویی، ظروف و …';

        $comparisonRows = [
            [
                'key' => 'tejarat-bnpl',
                'label' => 'BNPL بانک تجارت',
                'icon' => null,
                'ceil' => 'تا ۵۰۰ میلیون تومان',
                'categories' => $homeCategories,
                'installments' => '۳، ۶، ۱۲ و ۱۸ ماهه',
                'guarantee' => 'بدون نیاز به ضامن',
                'credit_grades' => ['B', 'C'],
                'special' => 'ثبت‌نام در اپ بانک تجارت؛ انتخاب درگاه در checkout '.$siteName,
                'buy_url' => route('products.index'),
            ],
            [
                'key' => 'kalapi',
                'label' => 'کالاپی',
                'icon' => null,
                'ceil' => 'تا ۳۰۰ میلیون تومان',
                'categories' => $homeCategories,
                'installments' => '۶، ۱۲ و ۲۴ ماهه',
                'guarantee' => 'چک یا سفته (طبق پرونده)',
                'credit_grades' => ['A', 'B'],
                'special' => 'مشتریان دارای اعتبار کالاپی',
                'buy_url' => route('products.index'),
            ],
            [
                'key' => 'nopay',
                'label' => $gateways->definition('nopay')['label'] ?: 'نوپی',
                'icon' => $icon('nopay'),
                'ceil' => 'تا ۲۰۰ میلیون تومان',
                'categories' => $homeCategories,
                'installments' => '۲، ۳، ۴ و ۶ ماهه',
                'guarantee' => 'بدون ضامن',
                'credit_grades' => ['B', 'C', 'D'],
                'special' => 'پرداخت CPG نوپی در '.$siteName,
                'buy_url' => route('products.index'),
            ],
            [
                'key' => 'bajet',
                'label' => 'بتا — بانک رفاه',
                'icon' => $icon('bajet'),
                'ceil' => 'تا ۱۵۰ میلیون تومان',
                'categories' => $homeCategories,
                'installments' => '۳، ۶ و ۱۲ ماهه',
                'guarantee' => 'بدون نیاز به ضامن',
                'credit_grades' => ['A', 'B', 'C'],
                'special' => 'اعتبار باجت‌پی (بتا) بانک رفاه',
                'buy_url' => route('products.index'),
            ],
            [
                'key' => 'tara',
                'label' => $gateways->definition('tara')['label'] ?: 'تارا',
                'icon' => $icon('tara'),
                'ceil' => 'تا ۳۰۰ میلیون تومان',
                'categories' => $homeCategories.', وسایل نقلیه سبک (طبق گروه کالا)',
                'installments' => '۱۲، ۲۴ و ۳۶ ماهه',
                'guarantee' => 'چک',
                'credit_grades' => ['A', 'B', 'C'],
                'special' => 'نیاز به کیف پول فعال تارا',
                'buy_url' => route('products.index'),
            ],
            [
                'key' => 'digipay',
                'label' => $gateways->definition('digipay')['label'] ?: 'دیجی‌پی',
                'icon' => $icon('digipay'),
                'ceil' => 'تا ۲۰۰ میلیون تومان',
                'categories' => $homeCategories,
                'installments' => '۲، ۳، ۴ و ۶ ماهه',
                'guarantee' => 'بدون ضامن',
                'credit_grades' => ['B', 'C'],
                'special' => 'سقف و اقساط نهایی در اپ دیجی‌پی',
                'buy_url' => route('plans.digipay-leasing'),
            ],
            [
                'key' => 'diba',
                'label' => 'دیبا کارت',
                'icon' => null,
                'ceil' => 'تا ۱۰۰ میلیون تومان',
                'categories' => $homeCategories,
                'installments' => '۶ و ۱۲ ماهه',
                'guarantee' => 'بدون ضامن',
                'credit_grades' => ['B', 'D'],
                'special' => 'دارندگان دیبا کارت فعال',
                'buy_url' => route('products.index'),
            ],
        ];

        return view('plans.how-to-buy', [
            'siteName' => $siteName,
            'seoImage' => $ogImage,
            'comparisonRows' => $comparisonRows,
        ]);
    }
}
