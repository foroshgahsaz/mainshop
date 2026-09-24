<?php

namespace App\Services\Product;

class ProductDeletionResult
{
    public function __construct(
        public int $deleted = 0,
        public int $archived = 0,
    ) {}

    public function message(): string
    {
        if ($this->deleted === 0 && $this->archived === 0) {
            return 'هیچ محصولی حذف نشد.';
        }

        $parts = [];

        if ($this->deleted > 0) {
            $parts[] = "{$this->deleted} محصول حذف شد";
        }

        if ($this->archived > 0) {
            $parts[] = "{$this->archived} محصول به‌دلیل وجود در سفارش‌ها آرشیو شد";
        }

        return implode(' و ', $parts).'.';
    }
}
