<?php

return [

    'default' => env('PAYMENT_GATEWAY', 'zarinpal'),

    'gateways' => [

        'zarinpal' => [
            'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
            'sandbox' => env('ZARINPAL_SANDBOX', true),
            'callback_url' => env('ZARINPAL_CALLBACK_URL', '/payment/callback'),
        ],

    ],

    'currency' => 'IRT',

];
