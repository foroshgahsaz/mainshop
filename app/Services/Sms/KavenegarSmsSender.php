<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KavenegarSmsSender implements SmsSender
{
    public function sendOtp(string $phone, string $code): void
    {
        $apiKey = app(\App\Services\Settings\SettingsService::class)->kavenegar()['api_key']
            ?: config('sms.kavenegar.api_key');
        $template = app(\App\Services\Settings\SettingsService::class)->kavenegar()['otp_template']
            ?: config('sms.kavenegar.otp_template');

        if (empty($apiKey)) {
            throw new RuntimeException('کلید API کاوه‌نگار تنظیم نشده است.');
        }

        $receptor = $this->normalizePhone($phone);

        if ($template) {
            $response = Http::timeout(15)->get(
                "https://api.kavenegar.com/v1/{$apiKey}/verify/lookup.json",
                [
                    'receptor' => $receptor,
                    'token' => $code,
                    'template' => $template,
                ]
            );
        } else {
            $sender = app(\App\Services\Settings\SettingsService::class)->kavenegar()['sender']
                ?: config('sms.kavenegar.sender');
            $message = "کد تایید چاپینو: {$code}";

            $response = Http::timeout(15)->get(
                "https://api.kavenegar.com/v1/{$apiKey}/sms/send.json",
                array_filter([
                    'receptor' => $receptor,
                    'sender' => $sender,
                    'message' => $message,
                ])
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException('ارسال پیامک با خطا مواجه شد.');
        }

        $status = (int) data_get($response->json(), 'return.status', 0);

        if ($status !== 200) {
            $message = data_get($response->json(), 'return.message', 'خطای ناشناخته');

            throw new RuntimeException('کاوه‌نگار: '.$message);
        }
    }

    public function sendTransactional(string $phone, string $message): void
    {
        $apiKey = app(\App\Services\Settings\SettingsService::class)->kavenegar()['api_key']
            ?: config('sms.kavenegar.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('کلید API کاوه‌نگار تنظیم نشده است.');
        }

        $receptor = $this->normalizePhone($phone);
        $sender = app(\App\Services\Settings\SettingsService::class)->kavenegar()['sender']
            ?: config('sms.kavenegar.sender');

        $response = Http::timeout(15)->get(
            "https://api.kavenegar.com/v1/{$apiKey}/sms/send.json",
            array_filter([
                'receptor' => $receptor,
                'sender' => $sender,
                'message' => $message,
            ])
        );

        if (! $response->successful()) {
            throw new RuntimeException('ارسال پیامک با خطا مواجه شد.');
        }

        $status = (int) data_get($response->json(), 'return.status', 0);

        if ($status !== 200) {
            $message = data_get($response->json(), 'return.message', 'خطای ناشناخته');

            throw new RuntimeException('کاوه‌نگار: '.$message);
        }
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone) ?? $phone;

        if (str_starts_with($phone, '98') && strlen($phone) === 12) {
            return '0'.substr($phone, 2);
        }

        return $phone;
    }
}
