<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\Product\ProductExcelExportService;
use Tests\TestCase;

class ProductExcelExportServiceTest extends TestCase
{
    public function test_row_values_match_requested_columns(): void
    {
        $product = new Product([
            'name' => 'یخچال سامسونگ',
            'sku' => 'SKU-100',
            'price' => 45_000_000,
            'sale_price' => 42_000_000,
            'stock' => 5,
            'is_active' => true,
            'is_not_for_sale' => false,
        ]);

        $row = (new ProductExcelExportService)->rowValues($product);

        $this->assertSame('SKU-100', $row[0]);
        $this->assertSame('یخچال سامسونگ', $row[1]);
        $this->assertSame(45_000_000, $row[2]);
        $this->assertSame(42_000_000, $row[3]);
        $this->assertSame(5, $row[4]);
        $this->assertSame('فعال', $row[5]);
        $this->assertSame('موجود', $row[6]);
    }

    public function test_header_columns(): void
    {
        $headers = (new ProductExcelExportService)->headerLabels();

        $this->assertContains('کد کالا', $headers);
        $this->assertContains('نام محصول', $headers);
        $this->assertContains('قیمت ویژه (تومان)', $headers);
    }
}