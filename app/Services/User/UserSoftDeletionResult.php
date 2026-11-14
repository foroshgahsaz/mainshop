<?php

namespace App\Services\User;

class UserSoftDeletionResult
{
    public function __construct(
        public readonly int $deleted,
        public readonly int $skipped,
    ) {}

    public function message(): string
    {
        $parts = [];

        if ($this->deleted > 0) {
            $parts[] = "{$this->deleted} کاربر از لیست پنهان شد";
        }

        if ($this->skipped > 0) {
            $parts[] = "{$this->skipped} کاربر به‌خاطر داشتن سفارش حذف نشد";
        }

        return $parts !== [] ? implode('؛ ', $parts) : 'موردی پردازش نشد';
    }
}
