<?php

namespace App\Services\Shop;

use App\Models\Brand;
use App\Models\ProductFamily;
use App\Models\ProductTemplate;
use Illuminate\Support\Collection;

class ChineseServiceBrandPopupService
{
    /** @return array{family_id: int, family_name: string, brands: list<array{name: string, url: string}>}|null */
    public function payload(): ?array
    {
        $family = $this->resolveFamily();
        if ($family === null) {
            return null;
        }

        $labels = config('shop.homepage.chinese_service_popup.brand_labels', []);
        if ($labels === []) {
            return null;
        }

        $brands = [];
        foreach ($labels as $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }

            $brands[] = [
                'name' => $label,
                'url' => $this->resolveListingUrl($family->id, $label),
            ];
        }

        if ($brands === []) {
            return null;
        }

        return [
            'family_id' => (int) $family->id,
            'family_name' => (string) $family->name,
            'brands' => $brands,
        ];
    }

    private function resolveFamily(): ?ProductFamily
    {
        $slug = (string) config('shop.homepage.chinese_service_popup.family_slug', 'chinese-service');
        $names = config('shop.homepage.chinese_service_popup.family_names', ['سرویس چینی']);

        return ProductFamily::query()
            ->where('is_active', true)
            ->where(function ($q) use ($slug, $names) {
                $q->where('slug', $slug);
                foreach ((array) $names as $name) {
                    $name = trim((string) $name);
                    if ($name !== '') {
                        $q->orWhere('name', $name);
                    }
                }
            })
            ->first();
    }

    private function resolveListingUrl(int $familyId, string $label): string
    {
        $template = ProductTemplate::query()
            ->where('product_family_id', $familyId)
            ->where('is_active', true)
            ->where('name', $label)
            ->first();

        if ($template !== null) {
            return route('products.index', [
                'product_family_id' => $familyId,
                'product_template_id' => $template->id,
            ]);
        }

        $brand = Brand::query()
            ->where('is_active', true)
            ->where('name', $label)
            ->first();

        if ($brand !== null) {
            return route('products.index', [
                'product_family_id' => $familyId,
                'brand_id' => $brand->id,
            ]);
        }

        return route('products.index', [
            'product_family_id' => $familyId,
            'search' => $label,
        ]);
    }

    /** @return Collection<int, string> */
    public static function configuredBrandLabels(): Collection
    {
        return collect(config('shop.homepage.chinese_service_popup.brand_labels', []))
            ->map(fn ($label) => trim((string) $label))
            ->filter();
    }
}
