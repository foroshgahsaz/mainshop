<?php

return [

    /*
  |--------------------------------------------------------------------------
  | Homepage display image presets
  |--------------------------------------------------------------------------
  |
    | Display image presets per shop section (homepage, listing, product page, etc.).
    | Admin can override via settings group "homepage_images".
  |
  */

    'sections' => [

        'hero' => [
            'label' => 'اسلایدر — دسکتاپ',
            'mode' => 'cover',
            'width' => 1920,
            'height' => 480,
            'quality' => 90,
            'format' => 'webp',
            // Upload preset already crops to 1920×480; avoid a second cover pass (blur/artifacts).
            'enabled' => false,
        ],

        'hero_mobile' => [
            'label' => 'اسلایدر — موبایل',
            'mode' => 'cover',
            'width' => 750,
            'height' => 400,
            'quality' => 90,
            'format' => 'webp',
            'enabled' => false,
        ],

        'categories' => [
            'label' => 'دسته‌بندی‌های منتخب',
            'mode' => 'contain',
            'width' => 240,
            'height' => 240,
            'quality' => 85,
            'format' => 'webp',
            'enabled' => true,
        ],

        'deals' => [
            'label' => 'فروش ویژه',
            'mode' => 'contain',
            'width' => 400,
            'height' => 400,
            'quality' => 85,
            'format' => 'webp',
            'enabled' => true,
        ],

        'new_products' => [
            'label' => 'جدیدترین محصولات',
            'mode' => 'cover',
            'width' => 400,
            'height' => 500,
            'quality' => 85,
            'format' => 'webp',
            'enabled' => true,
        ],

        'best_sellers' => [
            'label' => 'پرفروش‌ترین‌ها',
            'mode' => 'cover',
            'width' => 320,
            'height' => 400,
            'quality' => 85,
            'format' => 'webp',
            'enabled' => true,
        ],

        'related_products' => [
            'label' => 'محصولات مشابه',
            'mode' => 'cover',
            'width' => 320,
            'height' => 400,
            'quality' => 85,
            'format' => 'webp',
            'enabled' => true,
        ],

        'product_listing' => [
            'label' => 'لیست محصولات (گرید)',
            'mode' => 'cover',
            'width' => 480,
            'height' => 600,
            'quality' => 85,
            'format' => 'webp',
            'enabled' => true,
        ],

        'product_main' => [
            'label' => 'صفحه محصول — تصویر اصلی',
            'mode' => 'cover',
            'width' => 900,
            'height' => 900,
            'quality' => 88,
            'format' => 'webp',
            'enabled' => true,
        ],

        'product_thumb' => [
            'label' => 'صفحه محصول — تامبنیل گالری',
            'mode' => 'cover',
            'width' => 144,
            'height' => 144,
            'quality' => 82,
            'format' => 'webp',
            'enabled' => true,
        ],

        'blog' => [
            'label' => 'مجله چینی بازار',
            'mode' => 'cover',
            'width' => 400,
            'height' => 160,
            'quality' => 85,
            'format' => 'webp',
            'enabled' => true,
        ],

        'rep_gallery' => [
            'label' => 'گالری پاپ‌آپ نماینده',
            'mode' => 'contain',
            'width' => 320,
            'height' => 320,
            'quality' => 80,
            'format' => 'webp',
            'enabled' => true,
        ],

    ],

];
