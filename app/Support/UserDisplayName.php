<?php

namespace App\Support;

final class UserDisplayName
{
    public static function isPlaceholder(?string $name): bool
    {
        $name = trim((string) $name);

        if ($name === '') {
            return true;
        }

        if (preg_match('/^کاربر \d{4}$/u', $name)) {
            return true;
        }

        return (bool) preg_match('/^کاربر [۰-۹]{4}$/u', $name);
    }
}
