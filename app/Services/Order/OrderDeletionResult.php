<?php

namespace App\Services\Order;

class OrderDeletionResult
{
    public function __construct(
        public int $deleted = 0,
        public int $skipped = 0,
    ) {}

    public function message(): string
    {
        if ($this->deleted === 0 && $this->skipped === 0) {
            return 'هیچ سفارشی حذف نشد.';
        }

        $parts = [];

        if ($this->deleted > 0) {
            $parts[] = "{$this->deleted} سفارش حذف شد";
        }

        if ($this->skipped > 0) {
            $parts[] = "{$this->skipped} سفارش با پرداخت موفق نادیده گرفته شد";
        }

        return implode(' و ', $parts).'.';
    }
}
