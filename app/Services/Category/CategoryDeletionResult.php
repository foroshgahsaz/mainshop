<?php

namespace App\Services\Category;

class CategoryDeletionResult
{
    public function __construct(
        public int $deletedProducts = 0,
        public int $archivedProducts = 0,
    ) {}

    public function message(): string
    {
        if ($this->deletedProducts === 0 && $this->archivedProducts === 0) {
            return 'دسته‌بندی حذف شد.';
        }

        $parts = ['دسته‌بندی حذف شد'];

        if ($this->deletedProducts > 0) {
            $parts[] = "{$this->deletedProducts} محصول حذف شد";
        }

        if ($this->archivedProducts > 0) {
            $parts[] = "{$this->archivedProducts} محصول به‌دلیل وجود در سفارش‌ها به دسته «عمومی» منتقل و آرشیو شد";
        }

        return implode('؛ ', $parts).'.';
    }
}
