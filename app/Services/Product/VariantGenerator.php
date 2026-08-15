<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

class VariantGenerator
{
    public function generate(Product $product): int
    {
        $product->load(['attributes' => fn ($q) => $q->wherePivot('is_variation', true)->with('values')]);

        $attributeGroups = $product->attributes
            ->map(fn ($attribute) => $attribute->values->where('is_active', true)->values())
            ->filter(fn (Collection $values) => $values->isNotEmpty())
            ->values();

        if ($attributeGroups->isEmpty()) {
            return 0;
        }

        $combinations = $this->cartesian($attributeGroups);
        $created = 0;
        $basePrice = $product->effective_price;

        foreach ($combinations as $values) {
            $name = collect($values)->pluck('value')->implode(' / ');
            $skuSuffix = collect($values)->pluck('id')->implode('-');

            $variant = ProductVariant::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => $name,
                ],
                [
                    'price' => $basePrice,
                    'stock' => 0,
                    'sku' => $product->sku ? $product->sku.'-'.$skuSuffix : null,
                    'is_active' => true,
                ]
            );

            if ($variant->wasRecentlyCreated) {
                $created++;
            }

            $variant->attributeValues()->sync(collect($values)->pluck('id'));
        }

        return $created;
    }

    /**
     * @param  Collection<int, Collection<int, \App\Models\AttributeValue>>  $groups
     * @return array<int, array<int, \App\Models\AttributeValue>>
     */
    protected function cartesian(Collection $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $append = [];
            foreach ($result as $product) {
                foreach ($group as $item) {
                    $append[] = array_merge($product, [$item]);
                }
            }
            $result = $append;
        }

        return $result;
    }
}
