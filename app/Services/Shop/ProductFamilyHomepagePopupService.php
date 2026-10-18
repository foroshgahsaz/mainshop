<?php

namespace App\Services\Shop;

use App\Models\ProductFamily;

class ProductFamilyHomepagePopupService
{
    /**
     * @return array{family_id: int, family_name: string, items: list<array{label: string, url: string}>}|null
     */
    public function payloadForFamily(ProductFamily $family): ?array
    {
        if (! $family->homepage_popup_enabled) {
            return null;
        }

        $family->loadMissing([
            'homepagePopupItems' => fn ($q) => $q->where('is_active', true)->orderBy('position')->orderBy('id'),
        ]);

        $items = [];
        foreach ($family->homepagePopupItems as $row) {
            $label = trim((string) $row->label);
            $url = trim((string) $row->url);
            if ($label === '' || $url === '') {
                continue;
            }

            $items[] = [
                'label' => $label,
                'url' => $url,
            ];
        }

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
