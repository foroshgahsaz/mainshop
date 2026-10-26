<?php

/**
 * Persistent log storage (same pattern as config/backup.php).
 *
 * On Runflare/K8s: mount /data once; logs live in /data/logs alongside database-backups.
 */
return [

    'directory' => env('SHOP_LOGS_DIRECTORY') ?: (
        rtrim((string) env('FILESYSTEM_PUBLIC_ROOT', ''), '/\\') === '/data'
            ? '/data/logs'
            : storage_path('logs')
    ),

    'payments' => [
        /** Days shown in admin payment log viewer (recent table). */
        'list_days' => (int) env('PAYMENT_LOG_LIST_DAYS', 30),
        /** Plain .log files older than this are zipped (not deleted). */
        'archive_after_days' => (int) env('PAYMENT_LOG_ARCHIVE_AFTER_DAYS', 30),
        /** Monolog daily rotation retention for payments-*.log files. */
        'monolog_days' => (int) env('LOG_PAYMENTS_DAYS', 45),
    ],

    'laravel' => [
        'list_days' => (int) env('LARAVEL_LOG_LIST_DAYS', 20),
        'monolog_days' => (int) env('LOG_DAILY_DAYS', 14),
    ],

];
