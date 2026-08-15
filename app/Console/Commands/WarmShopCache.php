<?php

namespace App\Console\Commands;

use App\Services\Cache\ShopCacheService;
use Illuminate\Console\Command;

class WarmShopCache extends Command
{
    protected $signature = 'shop:cache-warm';

    protected $description = 'پیش‌گرم‌کردن کش فروشگاه (صفحه اصلی، دسته‌ها، لیست محصولات)';

    public function handle(ShopCacheService $cache): int
    {
        $driver = config('cache.default');
        $this->info("Cache driver: {$driver}");

        if (! in_array($driver, ['redis', 'memcached'], true)) {
            $this->warn('برای بهترین عملکرد CACHE_STORE=redis تنظیم کنید.');
        }

        $this->info('در حال گرم‌کردن کش...');
        $cache->warm();
        $this->info('✅ کش فروشگاه آماده است.');

        return self::SUCCESS;
    }
}
