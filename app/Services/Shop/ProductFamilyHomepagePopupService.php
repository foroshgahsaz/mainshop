<?php

namespace App\Services\Shop;

use App\Models\ProductFamily;
use App\Models\ProductFamilyHomepagePopupItem;

class ProductFamilyHomepagePopupService
{
    /**
     * @return array{family_id: int, family_name: string, items: list<array{label: string, url: string}>}|null
     */
    /**
     * @return list<array{label: string, url: string}>
     */
    public function activeLinkItemsForFamily(ProductFamily $family): array
    {
        return ProductFamilyHomepagePopupItem::query()
            ->where('product_family_id', $family->id)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function (ProductFamilyHomepagePopupItem $row): ?array {
                $label = trim((string) $row->label);
                $url = trim((string) $row->url);
                if ($label === '' || $url === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function payloadForFamily(ProductFamily $family): ?array
    {
        if (! $family->homepage_popup_enabled) {
            return null;
        }

        $items = $this->activeLinkItemsForFamily($family);

        if ($items === []) {
            return null;
        }

        return [
            'family_id' => (int) $family->id,
            'family_name' => (string) $family->name,
            'items' => $items,
        ];
    }

    /**
     * @param  iterable<int, ProductFamily>  $families
     * @return array<int, array{family_id: int, family_name: string, items: list<array{label: string, url: string}>}>
     */
    public function payloadsByFamilyId(iterable $families): array
    {
        $map = [];

        foreach ($families as $family) {
            if (! $family instanceof ProductFamily) {
                continue;
            }

            $payload = $this->payloadForFamily($family);
            if ($payload !== null) {
                $map[(int) $family->id] = $payload;
            }
        }

        return $map;
    }
}
