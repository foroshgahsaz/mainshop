<?php

return [

    'templates' => [

        'account_created' => [
            'label' => 'ایجاد حساب کاربری (ورود با موبایل)',
            'enabled' => true,
            'body' => '{site_name}: حساب کاربری برای شماره {phone} ایجاد شد. از خرید در فروشگاه ما خوش آمدید.',
        ],

        'order_placed' => [
            'label' => 'ثبت سفارش',
            'enabled' => true,
            'body' => '{site_name}: سفارش {order_code} ثبت شد. کالاها: {items}. مبلغ: {amount} تومان. روش پرداخت: {payment_method}.',
        ],

        'order_paid' => [
            'label' => 'پرداخت موفق سفارش',
            'enabled' => true,
            'body' => '{site_name}: پرداخت سفارش {order_code} با موفقیت انجام شد. مبلغ: {paid_amount} تومان. درگاه: {gateway}. کالاها: {items}. کد پیگیری پرداخت: {payment_tracking}.',
        ],

    ],

];
