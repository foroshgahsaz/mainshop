<?php

namespace App\Support;

use App\Rules\IranianNationalCode;

class UserNationalCodeCredentials
{
    public static function emailDomain(): string
    {
        return (string) config('shop.user_credentials.email_domain', 'chinibazar.ir');
    }

    public static function emailForNationalCode(string $nationalCode): string
    {
        $code = IranianNationalCode::normalize($nationalCode);

        return $code.'@'.self::emailDomain();
    }

    public static function passwordPlainForNationalCode(string $nationalCode): string
    {
        $code = IranianNationalCode::normalize($nationalCode);

        return '/*'.$code.'/*';
    }

    public static function applyIfNationalCodePresent(object $user): void
    {
        $code = IranianNationalCode::normalize((string) ($user->national_code ?? ''));
        if ($code === '') {
            return;
        }

        $user->email = self::emailForNationalCode($code);
        $user->password = self::passwordPlainForNationalCode($code);
    }

    public static function shouldApplyOnSave(object $user): bool
    {
        if (! $user->isDirty('national_code')) {
            return false;
        }

        return IranianNationalCode::normalize((string) ($user->national_code ?? '')) !== '';
    }
}
