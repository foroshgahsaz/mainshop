<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    public function sendOtp(string $phone, string $code, ?int $templateId = null): void
    {
        Log::info('OTP SMS', [
            'phone' => $phone,
            'code' => $code,
            'template_id' => $templateId,
        ]);
    }

    public function sendTransactional(string $phone, string $message): void
    {
        Log::info('Transactional SMS', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }
}
