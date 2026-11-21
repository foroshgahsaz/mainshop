<?php

namespace App\Services\User;

class UserDeletionResult
{
    public function __construct(
        public readonly int $deleted,
        public readonly int $skipped,
    ) {}

    public function message(): string
    {
        if ($this->deleted === 0 && $this->skipped > 0) {
            return 'هیچ کاربری حذف نشد؛ '.$this->skipped.' نفر سفارش دارند (فقط مدیر سیستم می‌تواند با «حذف کامل» پاک کند).';
        }

        if ($this->skipped === 0) {
            return $this->deleted === 1
                ? 'کاربر حذف شد.'
                : $this->deleted.' کاربر حذف شدند.';
        }

        return $this->deleted.' کاربر حذف شد؛ '.$this->skipped.' نفر به‌خاطر سفارش رد شدند.';
    }
}