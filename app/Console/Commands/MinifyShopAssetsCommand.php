<?php

namespace App\Console\Commands;

use App\Services\Shop\ShopAssetMinifier;
use Illuminate\Console\Command;

class MinifyShopAssetsCommand extends Command
{
    protected $signature = 'shop:minify-assets
                            {--only= : کلیدهای جدا شده با کاما (مثلاً custom_css,main_js)}';

    protected $description = 'ساخت فایل‌های .min.css و .min.js فروشگاه از روی منابع public/shop';

    public function handle(ShopAssetMinifier $minifier): int
    {
        $only = null;
        if (is_string($this->option('only')) && trim($this->option('only')) !== '') {
            $only = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('only')))));
        }

        $results = $minifier->build($only);

        if ($results === []) {
            $this->warn('فایلی برای فشرده‌سازی پیدا نشد.');

            return self::FAILURE;
        }

        foreach ($results as $row) {
            $saved = $row['bytes_before'] > 0
                ? (int) round((1 - $row['bytes_after'] / $row['bytes_before']) * 100)
                : 0;
            $this->line(sprintf(
                '%s → %s (%s KB → %s KB, ~%d%% کوچک‌تر)',
                $row['source'],
                $row['output'],
                number_format($row['bytes_before'] / 1024, 1),
                number_format($row['bytes_after'] / 1024, 1),
                $saved
            ));
        }

        $this->info('✅ فایل‌های min ساخته شدند. از ادمین مشخص کنید کدام‌ها در فروشگاه سرو شوند.');

        return self::SUCCESS;
    }
}
