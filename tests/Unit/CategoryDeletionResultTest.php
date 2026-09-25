<?php

namespace Tests\Unit;

use App\Services\Category\CategoryDeletionResult;
use Tests\TestCase;

class CategoryDeletionResultTest extends TestCase
{
    public function test_it_describes_deleted_products(): void
    {
        $result = new CategoryDeletionResult(deletedProducts: 2);

        $this->assertSame('دسته‌بندی حذف شد؛ 2 محصول حذف شد.', $result->message());
    }

    public function test_it_describes_archived_products(): void
    {
        $result = new CategoryDeletionResult(archivedProducts: 3);

        $this->assertSame(
            'دسته‌بندی حذف شد؛ 3 محصول به‌دلیل وجود در سفارش‌ها به دسته «عمومی» منتقل و آرشیو شد.',
            $result->message()
        );
    }
}
