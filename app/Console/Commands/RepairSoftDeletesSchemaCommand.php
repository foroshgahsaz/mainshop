<?php

namespace App\Console\Commands;

use App\Services\Shop\SoftDeletesSchemaRepair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RepairSoftDeletesSchemaCommand extends Command
{
    protected $signature = 'shop:repair-soft-deletes';

    protected $description = 'ایجاد ستون‌های orders/users.deleted_at اگر نیستند (رفع ۵۰۰ داشبورد)';

    public function handle(SoftDeletesSchemaRepair $repair): int
    {
        SoftDeletesSchemaRepair::forgetColumnCache();
        try {
            Cache::forget('soft_deletes_schema_ok');
            Cache::forget('soft_deletes_schema_ok_v2');
        } catch (\Throwable) {
        }

        $actions = $repair->ensure();

        if ($actions === []) {
            $this->info('ستون‌های soft-delete از قبل موجود بودند.');
        } else {
            $this->info('اضافه شد: '.implode(', ', $actions));
        }

        $repair->ensureOnce();

        return self::SUCCESS;
    }
}
