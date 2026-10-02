<?php

return [

    /*
    | شماره‌های پرسنل (ادمین/انبار) برای هشدار سفارش جدید — با کاما یا خط جدید جدا کنید.
    | در ادمین → متن پیامک‌های تراکنشی هم قابل ویرایش است.
    */
    'staff_phones_default' => '',

    'templates' => [

        'account_created' => [
            'label' => 'ایجاد حساب کاربری (ورود با موبایل)',
            'enabled' => true,
            'body' => '{site_name}: حساب کاربری برای شماره {phone} ایجاد شد. از خرید در فروشگاه ما خوش آمدید.',
        ],

        'order_placed' => [
            'label' => 'ثبت سفارش (مشتری)',
            'enabled' => true,
            'body' => '{site_name}: سفارش {order_code} ثبت شد. کالاها: {items}. مبلغ: {amount} تومان. روش پرداخت: {payment_method}.',
        ],

        'order_paid' => [
            'label' => 'پرداخت موفق سفارش',
            'enabled' => true,
            'body' => '{site_name}: پرداخت سفارش {order_code} با موفقیت انجام شد. مبلغ: {paid_amount} تومان. درگاه: {gateway}. کد پیگیری: {payment_tracking}.',
        ],

        'payment_failed' => [
            'label' => 'پرداخت ناموفق یا انصراف از درگاه',
            'enabled' => true,
            'body' => '{site_name}: پرداخت سفارش {order_code} انجام نشد. برای تکمیل خرید از حساب کاربری اقدام کنید.',
        ],

        'payment_partial_remaining' => [
            'label' => 'پرداخت جزئی — مبلغ باقی‌مانده',
            'enabled' => true,
            'body' => '{site_name}: بخشی از سفارش {order_code} پرداخت شد. مبلغ باقی‌مانده: {remaining_amount} تومان. از حساب کاربری پرداخت کنید.',
        ],

        'order_shipped' => [
            'label' => 'ارسال سفارش',
            'enabled' => true,
            'body' => '{site_name}: سفارش {order_code} ارسال شد.{tracking_suffix}',
        ],

        'order_delivered' => [
            'label' => 'تحویل سفارش',
            'enabled' => true,
            'body' => '{site_name}: سفارش {order_code} تحویل داده شد. از خرید شما سپاسگزاریم.',
        ],

        'order_canceled' => [
            'label' => 'لغو سفارش',
            'enabled' => true,
            'body' => '{site_name}: سفارش {order_code} لغو شد.',
        ],

        'order_expired_unpaid' => [
            'label' => 'انقضای مهلت پرداخت (لغو خودکار)',
            'enabled' => true,
            'body' => '{site_name}: سفارش {order_code} به‌دلیل عدم پرداخت در مهلت مقرر لغو شد.',
        ],

        'proforma_created' => [
            'label' => 'ثبت پیش‌فاکتور (مشتری نماینده)',
            'enabled' => true,
            'body' => '{site_name}: پیش‌فاکتور {order_code} ثبت شد. مبلغ: {amount} تومان. رزرو موجودی تا {reserved_until}.',
        ],

        'proforma_reservation_expired' => [
            'label' => 'انقضای رزرو پیش‌فاکتور',
            'enabled' => true,
            'body' => '{site_name}: مهلت رزرو پیش‌فاکتور {order_code} پایان یافت؛ موجودی آزاد شد. برای ادامه با نماینده تماس بگیرید.',
        ],

        'proforma_reservation_extended' => [
            'label' => 'تمدید رزرو پیش‌فاکتور',
            'enabled' => true,
            'body' => '{site_name}: مهلت رزرو پیش‌فاکتور {order_code} تا {reserved_until} تمدید شد.',
        ],

        'staff_new_order' => [
            'label' => 'هشدار پرسنل — سفارش جدید فروشگاه',
            'enabled' => false,
            'body' => '{site_name}: سفارش جدید {order_code} — {amount} تومان — مشتری {phone}',
        ],

        'staff_new_proforma' => [
            'label' => 'هشدار پرسنل — پیش‌فاکتور نماینده',
            'enabled' => false,
            'body' => '{site_name}: پیش‌فاکتور {order_code} توسط نماینده {representative_name} — {amount} تومان',
        ],

    ],

];
