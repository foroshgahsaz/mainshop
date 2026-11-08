<?php

namespace App\Console\Commands;

use App\Services\Cache\ShopCacheService;
use App\Services\Shop\MegaMenuSchemaRepair;
use Illuminate\Console\Command;

class RepairMegaMenuSchemaCommand extends Command
{
    protected $signature = 'shop:repair-mega-menu-schema';

    protected $description = 'ساخت/تکمیل جدول mega_menu_links و ستون‌های مرتبط (بدون وابستگی به جدول migrations)';

    public function handle(MegaMenuSchemaRepair $repair, ShopCacheService $cache): int
    {
        $actions = $repair->ensure();

        if ($actions === []) {
            $this->info('اسکیما مگا منو از قبل کامل است.');

            return self::SUCCESS;
        }

        foreach ($actions as $action) {
            $this->line('✓ '.$action);
        }

        $cache->forgetMenus();
        $cache->forgetCategory();

        $this->info('تعمیر اسکیما مگا منو تمام شد.');

        return self::SUCCESS;
    }
}
