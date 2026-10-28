<?php

namespace App\Support;

final class RialAmountInput
{
    public static function tomanFromInput(string $input): ?int
    {
        $digits = preg_replace('/\D/u', '', $input);

        if ($digits === null || $digits === '') {
            return null;
        }

        $rial = (int) $digits;

        if ($rial <= 0) {
            return null;
        }

        return (int) floor($rial / 10);
    }

    public static function formatRialFromToman(?int $toman): string
    {
        if ($toman === null || $toman <= 0) {
            return '';
        }

        return number_format(self::rialFromToman($toman));
    }

    public static function rialFromToman(int $toman): int
    {
        return $toman * 10;
    }

    public static function sanitizeFormattedInput(string $value): string
    {
        $digits = preg_replace('/\D/u', '', $value);

        if ($digits === null || $digits === '') {
            return '';
        }

        return number_format((int) $digits);
    }
}
