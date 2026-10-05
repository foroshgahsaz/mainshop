<?php

return [

    'database' => [
        'directory' => env('DB_BACKUP_DIRECTORY', storage_path('app/database-backups')),
        'keep' => (int) env('DB_BACKUP_KEEP', 2),
        'schedule_time' => env('DB_BACKUP_SCHEDULE_TIME', '18:00'),
        'schedule_timezone' => env('DB_BACKUP_SCHEDULE_TIMEZONE', 'Asia/Tehran'),
    ],

];
