<?php

namespace Tests\Unit;

use App\Services\Product\ProductDeletionResult;
use Tests\TestCase;

class ProductDeletionResultTest extends TestCase
{
    public function test_it_describes_deleted_products(): void
    {
        $result = new ProductDeletionResult(deleted: 3);

        $this->assertSame('3 محصول حذف شد.', $result->message());
    }

    public function test_it_describes_archived_products(): void
    {
        $result = new ProductDeletionResult(archived: 2);

        $this->assertSame('2 محصول به‌دلیل وجود در سفارش‌ها آرشیو شد.', $result->message());
    }

    public function test_it_describes_mixed_results(): void
    {
        $result = new ProductDeletionResult(deleted: 1, archived: 4);

        $this->assertSame(
            '1 محصول حذف شد و 4 محصول به‌دلیل وجود در سفارش‌ها آرشیو شد.',
            $result->message()
        );
    }
}
