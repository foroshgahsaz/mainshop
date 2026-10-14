<?php

return [

    'database' => [
        'directory' => env('DB_BACKUP_DIRECTORY') ?: (
            rtrim((string) env('FILESYSTEM_PUBLIC_ROOT', ''), '/\\') === '/data'
                ? '/data/database-backups'
                : storage_path('app/database-backups')
        ),
        'keep' => (int) env('DB_BACKUP_KEEP', 2),
        'schedule_time' => env('DB_BACKUP_SCHEDULE_TIME', '18:00'),
        'schedule_timezone' => env('DB_BACKUP_SCHEDULE_TIMEZONE', 'Asia/Tehran'),
        'mysqldump_path' => env('DB_BACKUP_MYSQLDUMP_PATH'),
        'php_fallback' => filter_var(env('DB_BACKUP_PHP_FALLBACK', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'files' => [
        'directory' => env('FILES_BACKUP_DIRECTORY') ?: (
            rtrim((string) env('FILESYSTEM_PUBLIC_ROOT', ''), '/\\') === '/data'
                ? '/data/files-backups'
                : storage_path('app/files-backups')
        ),
        'keep' => (int) env('FILES_BACKUP_KEEP', 1),
        'schedule_time' => env('FILES_BACKUP_SCHEDULE_TIME', env('DB_BACKUP_SCHEDULE_TIME', '19:00')),
        'schedule_timezone' => env('FILES_BACKUP_SCHEDULE_TIMEZONE', env('DB_BACKUP_SCHEDULE_TIMEZONE', 'Asia/Tehran')),
        'prefer_tar' => filter_var(env('FILES_BACKUP_PREFER_TAR', true), FILTER_VALIDATE_BOOLEAN),
        'process_timeout' => (int) env('FILES_BACKUP_TIMEOUT', 3600),
        'exclude_dir_names' => ['database-backups', 'files-backups', 'logs'],
        'roots' => null,
    ],

];
