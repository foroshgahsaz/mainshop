<?php

namespace App\Contracts;

interface SmsSender
{
    /**
     * @param  int|null  $templateId  شناسه قالب Verify (sms.ir). null = قالب ورود پیش‌فرض.
     */
    public function sendOtp(string $phone, string $code, ?int $templateId = null): void;

    public function sendTransactional(string $phone, string $message): void;
}
