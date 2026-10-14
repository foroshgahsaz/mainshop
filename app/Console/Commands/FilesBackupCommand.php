<?php

namespace App\Console\Commands;

use App\Services\Backup\FilesBackupService;
use Illuminate\Console\Command;

class FilesBackupCommand extends Command
{
    protected $signature = 'shop:files-backup {--manual : بک‌آپ دستی فایل‌ها}';

    protected $description = 'بک‌آپ فشرده از فایل‌های آپلود (public root) — فقط آخرین نسخه نگه داشته می‌شود';

    public function handle(FilesBackupService $backups): int
    {
        $manual = (bool) $this->option('manual');

        try {
            $path = $backups->create($manual);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('بک‌آپ فایل‌ها ذخیره شد: '.$path);

        return self::SUCCESS;
    }
}
