<?php

namespace App\Services\Payment;

final class PaymentAuditStep
{
    public const RECORD_CREATED = 5;

    public const ORDER_LINKED = 10;

    public const GATEWAY_CHECK = 15;

    public const INITIATE_START = 25;

    public const GATEWAY_REQUEST = 40;

    public const GATEWAY_RESPONSE = 55;

    public const REDIRECT_USER = 60;

    public const CALLBACK_RECEIVED = 70;

    public const VERIFY_START = 80;

    public const VERIFY_RESULT = 90;

    public const FINALIZED = 100;
}
