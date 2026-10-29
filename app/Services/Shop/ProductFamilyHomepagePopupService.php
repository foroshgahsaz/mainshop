<?php

namespace App\Services\Shop;

use App\Models\ProductFamily;
use App\Models\ProductFamilyHomepagePopupItem;
use App\Support\ShopFormatter;
use App\Support\ShopMedia;

class ProductFamilyHomepagePopupService
{
    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function sidebarLinksForFamily(ProductFamily $family): array
    {
        $items = $this->activeLinkItemsForFamily($family);

        if ($items !== []) {
            return $items;
        }

        return [$this->familyAsSidebarLink($family)];
    }

    /** @return array{label: string, url: string, image: ?string} */
    public function familyAsSidebarLink(ProductFamily $family): array
    {
        $image = trim((string) ($family->image ?? ''));

        return [
            'label' => (string) $family->name,
            'url' => route('products.index', ['product_family_id' => $family->id]),
            'image' => $image !== '' ? ShopFormatter::productFamilyImage($image) : null,
        ];
    }

    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function activeLinkItemsForFamily(ProductFamily $family, bool $resolveImageUrls = true): array
    {
        return collect($this->storedLinkItemsForFamily($family))
            ->map(function (array $link) use ($resolveImageUrls): array {
                $path = trim((string) ($link['image'] ?? ''));

                return [
                    'label' => $link['label'],
                    'url' => $link['url'],
                    'image' => $path === ''
                        ? null
                        : ($resolveImageUrls ? ShopMedia::url($path) : $path),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function storedLinkItemsForFamily(ProductFamily $family): array
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

                $imagePath = trim((string) ($row->image ?? ''));

                return [
                    'label' => $label,
                    'url' => $url,
                    'image' => $imagePath !== '' ? $imagePath : null,
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
