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

        return $this->payloadForFamily($family);
    }

    /** @return array{family_id: int, family_name: string, brands: list<array{name: string, url: string}>}|null */
    public function payloadForFamily(ProductFamily $family): ?array
    {
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

    public function matchesFamily(ProductFamily $family): bool
    {
        $slug = (string) config('shop.homepage.chinese_service_popup.family_slug', 'chinese-service');
        if ($slug !== '' && $family->slug === $slug) {
            return true;
        }

        $normalizedName = $this->normalizeLabel((string) $family->name);
        foreach ((array) config('shop.homepage.chinese_service_popup.family_names', ['سرویس چینی']) as $name) {
            if ($this->normalizeLabel((string) $name) === $normalizedName) {
                return true;
            }
        }

        return false;
    }

    private function resolveFamily(): ?ProductFamily
    {
        return ProductFamily::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get()
            ->first(fn (ProductFamily $family): bool => $this->matchesFamily($family));
    }

    private function normalizeLabel(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value;
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
