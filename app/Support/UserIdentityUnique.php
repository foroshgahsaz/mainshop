<?php

namespace App\Support;

use Illuminate\Validation\Rules\Unique;

/** قوانین یکتایی موبایل / ایمیل / کد ملی در جدول users. */
final class UserIdentityUnique
{
    public static function rule(string $column): Unique
    {
        return new Unique('users', $column);
    }

    /** @deprecated کاربران دیگر soft-delete ندارند؛ همان rule کافی است. */
    public static function withoutTrashed(Unique $rule): Unique
    {
        return $rule;
    }
}
