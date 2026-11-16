<?php

namespace App\Support;

use Illuminate\Validation\Rules\Unique;

/** قوانین یکتایی هویت کاربر — رکوردهای soft-deleted را در نظر نمی‌گیرد. */
final class UserIdentityUnique
{
    public static function rule(string $column): Unique
    {
        return self::withoutTrashed(new Unique('users', $column));
    }

    public static function withoutTrashed(Unique $rule): Unique
    {
        return $rule->whereNull('deleted_at');
    }
}
