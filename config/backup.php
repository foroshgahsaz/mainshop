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

];
