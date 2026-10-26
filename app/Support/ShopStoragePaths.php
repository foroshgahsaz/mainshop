<?php

namespace App\Support;

class ShopStoragePaths
{
    public static function logsDirectory(): string
    {
        $custom = config('shop.storage.logs_directory');

        if (is_string($custom) && $custom !== '') {
            return rtrim($custom, '/\\');
        }

        return self::logsDirectoryFromEnvironment();
    }

    /**
     * Used when config/logging.php loads before shop.php (alphabetical order).
     * Also used during config:cache so channel paths match SHOP_LOGS_DIRECTORY / /data.
     */
    public static function logsDirectoryFromEnvironment(): string
    {
        $envCustom = env('SHOP_LOGS_DIRECTORY');
        if (is_string($envCustom) && trim($envCustom) !== '') {
            return rtrim(trim($envCustom), '/\\');
        }

        $root = rtrim((string) env('FILESYSTEM_PUBLIC_ROOT', ''), '/\\');
        if ($root === '/data') {
            return '/data/logs';
        }

        return storage_path('logs');
    }

    public static function laravelLogPath(): string
    {
        return self::logsDirectory().DIRECTORY_SEPARATOR.'laravel.log';
    }

    public static function paymentsLogPath(): string
    {
        return self::logsDirectory().DIRECTORY_SEPARATOR.'payments.log';
    }

    public static function backupDirectory(): string
    {
        return (string) config('backup.database.directory');
    }

    public static function filesBackupDirectory(): string
    {
        return (string) config('backup.files.directory');
    }

    public static function usesCustomLogsDirectory(): bool
    {
        $custom = config('shop.storage.logs_directory');

        return is_string($custom) && $custom !== '';
    }

    public static function ensureDirectory(string $directory): void
    {
        StoragePermissionFixer::ensureWritableDirectory($directory);
    }

    /**
     * @return list<string>
     */
    public static function persistentDirectories(): array
    {
        return array_values(array_unique([
            self::logsDirectory(),
            self::backupDirectory(),
            self::filesBackupDirectory(),
        ]));
    }
}
