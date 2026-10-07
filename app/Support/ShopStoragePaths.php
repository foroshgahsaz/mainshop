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
        ]));
    }
}
