<?php

use App\Services\Payment\TaraGateway;
use App\Services\Payment\ZarinpalGateway;

return [

    'default' => env('PAYMENT_GATEWAY', 'zarinpal'),

    'gateways' => [

        'zarinpal' => [
            'driver' => ZarinpalGateway::class,
            'type' => 'cash',
            'label' => 'زرین‌پال',
            'description' => 'پرداخت نقدی آنلاین',
            'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
            'sandbox' => env('ZARINPAL_SANDBOX', true),
            'callback_url' => env('ZARINPAL_CALLBACK_URL', '/payment/callback'),
            'amount_unit' => env('ZARINPAL_AMOUNT_UNIT', 'toman'),
        ],

        'tara' => [
            'driver' => TaraGateway::class,
            'type' => 'credit',
            'label' => 'تارا',
            'description' => 'پرداخت اعتباری از کیف پول تارا',
            'username' => env('TARA_USERNAME', 'tara_ipg'),
            'password' => env('TARA_PASSWORD', 'tara_ipg@123_$'),
            'service_id' => env('TARA_SERVICE_ID', '101'),
            'sandbox' => env('TARA_SANDBOX', true),
            'base_url' => env('TARA_BASE_URL', 'https://pay.tara360.ir/pay'),
            'sandbox_base_url' => env('TARA_SANDBOX_BASE_URL', 'https://stage-pay.tara360.ir/pay'),
            'callback_url' => env('TARA_CALLBACK_URL', '/payment/callback/tara'),
            'amount_unit' => env('TARA_AMOUNT_UNIT', 'toman'),
            'client_ip' => env('TARA_CLIENT_IP'),
            'default_group' => env('TARA_DEFAULT_GROUP', '1'),
            'default_group_title' => env('TARA_DEFAULT_GROUP_TITLE', 'عمومی'),
            'refund_principal' => env('TARA_REFUND_PRINCIPAL', 'ipg_refund'),
            'refund_password' => env('TARA_REFUND_PASSWORD', '123456'),
            'refund_base_url' => env('TARA_REFUND_BASE_URL', 'https://club.tara-club.ir/club'),
            'sandbox_refund_base_url' => env('TARA_SANDBOX_REFUND_BASE_URL', 'https://stage.tara-club.ir/club'),
        ],

    ],

    'currency' => 'IRT',

];
