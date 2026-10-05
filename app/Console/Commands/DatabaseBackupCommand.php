<?php

namespace App\Console\Commands;

use App\Services\Backup\DatabaseBackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    protected $signature = 'shop:database-backup {--manual : بک‌آپ دستی (از پنل یا خط فرمان)}';

    protected $description = 'ایجاد بک‌آپ فشرده از دیتابیس و نگه‌داشتن فقط چند نسخه آخر';

    public function handle(DatabaseBackupService $backups): int
    {
        $manual = (bool) $this->option('manual');

        try {
            $path = $backups->create($manual);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('بک‌آپ ذخیره شد: '.$path);

        return self::SUCCESS;
    }
}
