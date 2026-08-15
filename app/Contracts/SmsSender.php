<?php

namespace App\Contracts;

interface SmsSender
{
    public function sendOtp(string $phone, string $code): void;

    public function sendTransactional(string $phone, string $message): void;
}
