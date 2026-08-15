<?php

namespace App\Support;

use App\Models\Product;

class ProductSpecRows
{
    /**
     * Build specification rows from already-loaded product relations (no extra queries).
     *
     * @return list<array{label: string, value: string}>
     */
    public static function for(Product $product): array
    {
        $rows = [];

        if ($product->relationLoaded('brand') && $product->brand) {
            $rows[] = ['label' => 'برند', 'value' => $product->brand->name];
        }

        if ($product->relationLoaded('category') && $product->category) {
            $rows[] = ['label' => 'دسته‌بندی', 'value' => $product->category->name];
        }

        if (filled($product->sku)) {
            $rows[] = ['label' => 'شناسه کالا', 'value' => $product->sku];
        }

        if ($product->weight) {
            $rows[] = ['label' => 'وزن', 'value' => number_format($product->weight).' گرم'];
        }

        if ($product->relationLoaded('variants')) {
            $grouped = [];

            foreach ($product->variants as $variant) {
                if (! $variant->relationLoaded('attributeValues')) {
                    continue;
                }

                foreach ($variant->attributeValues as $attributeValue) {
                    $attribute = $attributeValue->relationLoaded('attribute')
                        ? $attributeValue->attribute
                        : null;

                    if (! $attribute) {
                        continue;
                    }

                    $grouped[$attribute->name][$attributeValue->value] = true;
                }
            }

            foreach ($grouped as $name => $values) {
                $rows[] = [
                    'label' => $name,
                    'value' => implode('، ', array_keys($values)),
                ];
            }
        }

        return $rows;
    }
}
