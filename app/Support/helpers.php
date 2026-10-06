<?php

use App\Support\ShopDate;

if (! function_exists('site_name')) {
    function site_name(): string
    {
        return app(\App\Services\Settings\SettingsService::class)->site()['name'];
    }
}

if (! function_exists('shop_jalali')) {
    function shop_jalali(mixed $date, string $format = 'Y/m/d H:i', string $empty = '—'): string
    {
        return ShopDate::format($date, $format, $empty);
    }
}
