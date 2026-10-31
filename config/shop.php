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

    'user_credentials' => [
        /** ایمیل خودکار: {کد_ملی}@domain */
        'email_domain' => env('SHOP_USER_EMAIL_DOMAIN', 'chinibazar.ir'),
    ],

    'checkout' => [
        'unpaid_ttl_minutes' => (int) env('SHOP_UNPAID_TTL_MINUTES', 60),
        /** سفارش مستقیم فروشگاه (بدون ویزارد نماینده) — user_id مدیر/نمایندهٔ «چینی بازار» در production */
        'direct_representative_id' => (int) env('SHOP_DIRECT_REPRESENTATIVE_ID', 81),
    ],

    /** فاکتور PDF مالی: سهم ارزش افزوده از مبلغ قابل پرداخت (درصد، فقط نسخه مالی) */
    'invoice' => [
        'vat_percent' => (int) env('SHOP_INVOICE_VAT_PERCENT', 10),
    ],

    'rich_content' => [
        /** حداکثر حجم آپلود ویدئو در ادیتور (KB) */
        'video_max_size_kb' => (int) env('SHOP_RICH_CONTENT_VIDEO_MAX_KB', 102400),
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

    /** Bump css_version after shop CSS changes if CDN/browser keeps old custom.css */
    'assets' => [
        'css_version' => (int) env('SHOP_CSS_VERSION', 11),
    ],

];
