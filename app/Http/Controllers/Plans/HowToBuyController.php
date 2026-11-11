<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;
use App\Support\ShopMedia;
use Illuminate\View\View;

/**
 * راهنمای خرید اعتباری/اقساطی از چینی بازار — جدول مقایسهٔ طرح‌های فعال/معرفی‌شده در فروشگاه.
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

        $shop = route('products.index');

        $comparisonRows = [
            [
                'key' => 'tejarat-bnpl',
                'label' => 'BNPL بانک تجارت',
                'icon' => null,
                'ceil' => '۵۰ میلیون تومان',
                'categories' => 'لوازم خانگی، وسایل نقلیه، آلات موسیقی و ابزارآلات',
                'installments' => '۴ ماهه',
                'guarantee' => 'ضامن، سفته',
                'credit_grades' => ['A', 'B', 'C'],
                'special' => 'مخصوص کارمندان بانک تجارت، شرکت‌ها و سازمان‌ها؛ رتبه اعتباری A و B و C',
                'buy_url' => $shop,
            ],
            [
                'key' => 'kalapi',
                'label' => 'کالاپی',
                'icon' => null,
                'ceil' => '۳۰۰ میلیون تومان',
                'categories' => 'لوازم خانگی، وسایل نقلیه، آلات موسیقی و ابزارآلات',
                'installments' => '۱۲، ۲۴ و ۳۶ ماهه',
                'guarantee' => 'ضامن',
                'credit_grades' => ['A', 'B', 'C'],
                'special' => 'داشتن اپلیکیشن بام، رتبه اعتباری A و B و C، ضامن رسمی، چک دیجیتال؛ حداکثر تعداد ضمانت هر شخص ۵ نفر',
                'buy_url' => $shop,
            ],
            [
                'key' => 'nopay',
                'label' => $gateways->definition('nopay')['label'] ?: 'نوپی',
                'icon' => $icon('nopay'),
                'ceil' => '۳۰ میلیون تومان',
                'categories' => 'کلیه کالاها',
                'installments' => '۴ ماهه',
                'guarantee' => 'بدون نیاز به ضمانت',
                'credit_grades' => [],
                'special' => 'مخصوص فرهنگیان و کارمندان بانک',
                'buy_url' => $shop,
            ],
            [
                'key' => 'bajet',
                'label' => 'بتا — بانک رفاه',
                'icon' => $icon('bajet'),
                'ceil' => '۵۰۰ میلیون تومان',
                'categories' => 'لوازم خانگی، لپ‌تاپ، موبایل، موتور برق، دوچرخه، ابزارآلات، فرش و تهویه مطبوع',
                'installments' => '۱۲ و ۲۴ ماهه',
                'guarantee' => 'چک، سفته',
                'credit_grades' => ['A', 'B', 'C', 'D'],
                'special' => 'کالانو (باجت): رتبه اعتباری A و B و C و D بدون ضامن؛ سایر رتبه‌ها نیاز به ضامن به‌علاوه چک یا سفته الکترونیک',
                'buy_url' => $shop,
            ],
            [
                'key' => 'tara',
                'label' => $gateways->definition('tara')['label'] ?: 'تارا',
                'icon' => $icon('tara'),
                'ceil' => '۲۰۰ میلیون تومان',
                'categories' => 'کلیه کالاها',
                'installments' => '۹، ۱۲، ۱۸ و ۲۴ ماهه',
                'guarantee' => 'بدون نیاز به ضمانت',
                'credit_grades' => [],
                'special' => 'یک برگ چک صیادی، کارت پایان خدمت و داشتن حساب در بانک ملت',
                'buy_url' => $shop,
            ],
            [
                'key' => 'digipay',
                'label' => $gateways->definition('digipay')['label'] ?: 'دیجی‌پی',
                'icon' => $icon('digipay'),
                'ceil' => '۲۰۰ میلیون تومان',
                'categories' => 'لوازم خانگی، وسایل نقلیه، آلات موسیقی و ابزارآلات',
                'installments' => '۱۲ ماهه',
                'guarantee' => 'بدون نیاز به ضمانت',
                'credit_grades' => ['A', 'B', 'C'],
                'special' => 'چک یا سفته الکترونیک، رتبه اعتباری A و B و C، حساب در بانک تجارت یا ملت یا پارسیان',
                'buy_url' => route('plans.digipay-leasing'),
            ],
            [
                'key' => 'diba',
                'label' => 'دیبا کارت',
                'icon' => null,
                'ceil' => '۱۰۰ میلیون تومان',
                'categories' => 'لوازم خانگی، وسایل نقلیه، آلات موسیقی و ابزارآلات',
                'installments' => '۶ و ۱۲ ماهه',
                'guarantee' => 'بدون ضامن',
                'credit_grades' => ['B', 'D'],
                'special' => 'دارندگان دیبا کارت فعال',
                'buy_url' => $shop,
            ],
        ];

        return view('plans.how-to-buy', [
            'siteName' => $siteName,
            'seoImage' => $ogImage,
            'comparisonRows' => $comparisonRows,
        ]);
    }
}
