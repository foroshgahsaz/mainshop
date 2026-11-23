<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductExcelExportService
{
    /** @return list<string> */
    public function headerLabels(): array
    {
        return [
            'کد کالا',
            'نام محصول',
            'قیمت فروش (تومان)',
            'قیمت ویژه (تومان)',
            'تعداد',
            'وضعیت',
            'موجودی',
        ];
    }

    /**
     * @return list<string|int>
     */
    public function rowValues(Product $product): array
    {
        $stock = (int) ($product->stock ?? 0);

        return [
            (string) ($product->sku ?? ''),
            (string) ($product->name ?? ''),
            (int) ($product->price ?? 0),
            $product->sale_price !== null ? (int) $product->sale_price : '',
            $stock,
            $this->statusLabel($product),
            $this->availabilityLabel($product, $stock),
        ];
    }

    protected function statusLabel(Product $product): string
    {
        if ($product->trashed()) {
            return 'حذف‌شده';
        }

        return ($product->is_active ?? false) ? 'فعال' : 'غیرفعال';
    }

    protected function availabilityLabel(Product $product, int $stock): string
    {
        if ($product->isNotForSale()) {
            return 'غیرقابل فروش';
        }

        return $stock > 0 ? 'موجود' : 'ناموجود';
    }

    public function download(Builder $query, string $basename = 'products'): StreamedResponse
    {
        $filename = $basename.'-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($query): void {
            $writer = WriterFactory::createFromFile('products-export.xlsx');
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->headerLabels()));

            (clone $query)
                ->reorder()
                ->orderBy('id')
                ->select(['id', 'name', 'sku', 'price', 'sale_price', 'stock', 'is_active', 'is_not_for_sale', 'deleted_at'])
                ->chunkById(300, function ($products) use ($writer): void {
                    foreach ($products as $product) {
                        if (! $product instanceof Product) {
                            continue;
                        }

                        $writer->addRow(Row::fromValues($this->rowValues($product)));
                    }
                });

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}