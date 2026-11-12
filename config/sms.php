<?php

return [

    'driver' => env('SMS_DRIVER', 'log'),

    'kavenegar' => [
        'api_key' => env('KAVENEGAR_API_KEY'),
        'sender' => env('KAVENEGAR_SENDER'),
        'otp_template' => env('KAVENEGAR_OTP_TEMPLATE', 'verify'),
    ],

    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'template_id' => env('SMSIR_TEMPLATE_ID'),
        /** قالب Verify برای OTP تأیید انتقال مشتری بین نمایندگی‌ها */
        'transfer_template_id' => (int) env('SMSIR_TRANSFER_TEMPLATE_ID', 785592),
        'otp_parameter' => env('SMSIR_OTP_PARAMETER', 'Code'),
        'line_number' => env('SMSIR_LINE_NUMBER'),
        'resend_minutes' => (int) env('SMSIR_RESEND_MINUTES', 2),
    ],

];
