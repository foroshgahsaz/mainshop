<?php

return [

    'cache' => [
        'categories_ttl' => env('SHOP_CACHE_CATEGORIES_TTL', 3600),
        'products_ttl' => env('SHOP_CACHE_PRODUCTS_TTL', 900),
        'home_ttl' => env('SHOP_CACHE_HOME_TTL', 600),
        'shipping_ttl' => env('SHOP_CACHE_SHIPPING_TTL', 3600),
    ],

    'otp' => [
        'length' => 6,
        'expires_minutes' => 5,
        'throttle_seconds' => 120,
    ],

    'cart' => [
        'guest_prefix' => 'cart:guest:',
        'guest_ttl' => 60 * 24 * 7,
    ],

    'checkout' => [
        'unpaid_ttl_minutes' => (int) env('SHOP_UNPAID_TTL_MINUTES', 60),
    ],

    'representative' => [
        'catalog_cache_seconds' => (int) env('REP_CATALOG_CACHE_SECONDS', 300),
        'products_per_page' => (int) env('REP_PRODUCTS_PER_PAGE', 60),
        'default_shipping_amount' => (int) env('REP_DEFAULT_SHIPPING_AMOUNT', 0),
        'proforma_reservation_ttl_minutes' => (int) env('REP_PROFORMA_RESERVATION_TTL_MINUTES', 1440),
        'proforma_reservation_extend_minutes' => (int) env('REP_PROFORMA_RESERVATION_EXTEND_MINUTES', 1440),
    ],

    /*
    |--------------------------------------------------------------------------
    | Persistent storage paths (Runflare / multi-pod)
    |--------------------------------------------------------------------------
    |
    | On Runflare, mount ONE shared disk at /data (images already use this).
    | Point logs and DB backups under /data — separate mounts at /storage/logs
    | often miss Laravel's real path (e.g. /var/www/storage/logs).
    |
    */
    'storage' => [
        'logs_directory' => env('SHOP_LOGS_DIRECTORY') ?: (
            rtrim((string) env('FILESYSTEM_PUBLIC_ROOT', ''), '/\\') === '/data'
                ? '/data/logs'
                : null
        ),
    ],

];
