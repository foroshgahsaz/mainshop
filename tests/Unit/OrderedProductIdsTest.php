<?php

namespace Tests\Unit;

use App\Support\OrderedProductIds;
use PHPUnit\Framework\TestCase;

class OrderedProductIdsTest extends TestCase
{
    public function test_parses_comma_and_persian_separator(): void
    {
        $ids = OrderedProductIds::parse('3، 1, 3, 0');

        $this->assertSame([3, 1], $ids);
    }
}