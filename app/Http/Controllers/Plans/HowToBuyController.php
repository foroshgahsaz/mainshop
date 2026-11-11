<?php

namespace App\Http\Controllers\Plans;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Settings\SettingsService;
use App\Support\ShopMedia;
use Illuminate\View\View;

/**
 * راهنمای خرید اعتباری/اقساطی — جدول مقایسهٔ طرح‌ها (مرجع بازاریابی؛ جزئیات نهایی نزد تأمین‌کننده).
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
                'key' => 'aloopy',
                'label' => 'الوپی',
                'icon' => null,
                'ceil' => '۲۸۰ میلیون تومان',
                'categories' => 'کلیه کالاها',
                'installments' => '۶، ۱۲، ۱۸، ۲۴ و ۳۶ ماهه',
                'guarantee' => 'چک',
                'credit_grades' => ['A', 'B'],
                'special' => 'رتبه اعتباری A و B و داشتن دسته چک',
                'buy_url' => $shop,
            ],
            [
                'key' => 'vibe',
                'label' => 'وایب',
                'icon' => null,
                'ceil' => '۲۰۰ میلیون تومان',
                'categories' => 'کالای دیجیتال',
                'installments' => '۹ ماهه',
                'guarantee' => 'بدون نیاز به ضمانت',
                'credit_grades' => [],
                'special' => 'مخصوص بازنشستگان، مستمری‌بگیران و کارمندان بانک رفاه',
                'buy_url' => $shop,
            ],
            [
                'key' => 'bajet-kalano',
                'label' => 'کالانو (باجت)',
                'icon' => $icon('bajet'),
                'ceil' => '۵۰۰ میلیون تومان',
                'categories' => 'لوازم خانگی، لپ‌تاپ، موبایل، موتور برق، دوچرخه، ابزارآلات، فرش و تهویه مطبوع',
                'installments' => '۱۲ و ۲۴ ماهه',
                'guarantee' => 'چک، سفته',
                'credit_grades' => ['A', 'B', 'C', 'D'],
                'special' => 'رتبه اعتباری A و B و C و D بدون ضامن؛ سایر رتبه‌ها نیاز به ضامن به‌علاوه چک یا سفته الکترونیک',
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
                'key' => 'balon',
                'label' => 'بالون',
                'icon' => null,
                'ceil' => '۱۷۰ میلیون تومان',
                'categories' => 'کلیه کالاها',
                'installments' => '۶، ۱۲ و ۱۸ ماهه',
                'guarantee' => 'بدون نیاز به ضمانت',
                'credit_grades' => ['A', 'B'],
                'special' => 'یک برگ چک صیادی، مدارک شغلی و داشتن حساب در بلو بانک (رتبه اعتباری A و B)',
                'buy_url' => $shop,
            ],
            [
                'key' => 'refahi-card',
                'label' => 'کارت رفاهی',
                'icon' => null,
                'ceil' => '۵۰۰ میلیون تومان',
                'categories' => 'لوازم خانگی، وسایل نقلیه، آلات موسیقی و ابزارآلات',
                'installments' => '۲۴ ماهه',
                'guarantee' => 'ضامن',
                'credit_grades' => [],
                'special' => 'مناسب برای تمامی افراد',
                'buy_url' => $shop,
            ],
            [
                'key' => 'jet-vam',
                'label' => 'جت وام',
                'icon' => null,
                'ceil' => '۱۰۰ میلیون تومان',
                'categories' => 'کلیه کالاها',
                'installments' => '۱۲ و ۲۴ ماهه',
                'guarantee' => 'چک، ضامن',
                'credit_grades' => [],
                'special' => 'یک برگ چک ضمانت شخص و یک برگ چک ضامن؛ برای آقایان زیر ۵۵ سال کارت پایان خدمت لازم است',
                'buy_url' => $shop,
            ],
            [
                'key' => 'beta-refah',
                'label' => 'بتا بانک رفاه',
                'icon' => null,
                'ceil' => '۱۰۰ میلیون تومان',
                'categories' => 'لوازم خانگی',
                'installments' => '۶ و ۹ ماهه',
                'guarantee' => 'بدون نیاز به ضمانت',
                'credit_grades' => [],
                'special' => 'دریافت مستمری از بانک رفاه (بازنشستگان تأمین اجتماعی)',
                'buy_url' => $shop,
            ],
            [
                'key' => 'azki',
                'label' => 'ازکی وام',
                'icon' => null,
                'ceil' => '۳۰۰ میلیون تومان',
                'categories' => 'کلیه کالاها',
                'installments' => '۱۲ و ۱۸ ماهه',
                'guarantee' => 'چک',
                'credit_grades' => ['A', 'B', 'C'],
                'special' => 'چک و مدارک شغلی (رتبه اعتباری A و B و C)',
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
                'key' => 'firoozeh',
                'label' => 'فیروزه',
                'icon' => null,
                'ceil' => '۲۰۰ میلیون تومان',
                'categories' => 'همه محصولات',
                'installments' => '۱ و ۴ ماهه',
                'guarantee' => 'سپرده',
                'credit_grades' => [],
                'special' => 'سپرده در صندوق فیروزه',
                'buy_url' => $shop,
            ],
            [
                'key' => 'greenpay',
                'label' => 'گرین‌پی',
                'icon' => null,
                'ceil' => '۱ میلیارد تومان',
                'categories' => 'لوازم خانگی، وسایل نقلیه، آلات موسیقی و ابزارآلات',
                'installments' => 'کسر از مبلغ گندم',
                'guarantee' => 'بدون نیاز به ضمانت',
                'credit_grades' => [],
                'special' => 'کشاورزانی که محصول خود را به دولت می‌فروشند',
                'buy_url' => $shop,
            ],
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
        ];

        return view('plans.how-to-buy', [
            'siteName' => $siteName,
            'seoImage' => $ogImage,
            'comparisonRows' => $comparisonRows,
        ]);
    }
}
