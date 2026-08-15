<?php

return [

    'driver' => env('SMS_DRIVER', 'log'),

    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
        'sender' => env('KAVENEGAR_SENDER'),
        'otp_template' => env('KAVENEGAR_OTP_TEMPLATE', 'verify'),
    ],

];
