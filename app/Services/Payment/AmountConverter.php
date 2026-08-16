<?php

namespace App\Services\Payment;

class AmountConverter
{
    public const UNIT_TOMAN = 'toman';

    public const UNIT_RIAL = 'rial';

    public static function toGateway(int $shopAmount, string $unit): int
    {
        return $unit === self::UNIT_TOMAN ? $shopAmount * 10 : $shopAmount;
    }

    public static function fromGateway(int|string $gatewayAmount, string $unit): int
    {
        $amount = (int) $gatewayAmount;

        return $unit === self::UNIT_TOMAN ? intdiv($amount, 10) : $amount;
    }
}
