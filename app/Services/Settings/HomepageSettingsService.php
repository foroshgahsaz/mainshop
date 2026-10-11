<?php

namespace App\Services\Settings;

use App\Models\Product;
use Illuminate\Support\Collection;

class HomepageSettingsService
{
    public const SETTINGS_GROUP = 'homepage';

    public const TAXONOMY_CATEGORIES = 'categories';

    public const TAXONOMY_PRODUCT_FAMILIES = 'product_families';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        $defaults = $this->defaults();
        $stored = $this->settings->get(self::SETTINGS_GROUP, 'data');

        if (! is_string($stored) || $stored === '') {
            return $defaults;
        }

        $decoded = json_decode($stored, true);

        if (! is_array($decoded)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $this->normalize($decoded));
    }

    /** @return array<string, mixed> */
    public function forAdminForm(): array
    {
        $data = $this->all();

        return [
            'taxonomy_mode' => $data['taxonomy_mode'],
            'new_products_enabled' => $data['new_products_enabled'],
            'new_product_ids' => $this->formatIdsForAdmin($data['new_product_ids']),
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function saveFromAdminForm(array $data): void
    {
        $normalized = $this->normalize([
            'taxonomy_mode' => $data['taxonomy_mode'] ?? self::TAXONOMY_CATEGORIES,
            'new_products_enabled' => $data['new_products_enabled'] ?? true,
            'new_product_ids' => $data['new_product_ids'] ?? '',
        ]);

        $this->settings->set(
            self::SETTINGS_GROUP,
            'data',
            json_encode($normalized, JSON_UNESCAPED_UNICODE)
        );
    }

    public function taxonomyMode(): string
    {
        $mode = (string) ($this->all()['taxonomy_mode'] ?? self::TAXONOMY_CATEGORIES);

        return in_array($mode, [self::TAXONOMY_CATEGORIES, self::TAXONOMY_PRODUCT_FAMILIES], true)
            ? $mode
            : self::TAXONOMY_CATEGORIES;
    }

    public function newProductsEnabled(): bool
    {
        return (bool) ($this->all()['new_products_enabled'] ?? true);
    }

    /** @return list<int> */
    public function newProductIds(): array
    {
        return $this->all()['new_product_ids'];
    }

    /**
     * @param  callable(): \Illuminate\Database\Eloquent\Builder<Product>  $productQuery
     */
    public function resolveNewProducts(callable $productQuery): Collection
    {
        if (! $this->newProductsEnabled()) {
            return collect();
        }

        $ids = $this->newProductIds();

        if ($ids === []) {
            return $productQuery()->latest()->take(8)->get();
        }

        $products = $productQuery()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $products->get($id))
            ->filter()
            ->values();
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'taxonomy_mode' => self::TAXONOMY_CATEGORIES,
            'new_products_enabled' => true,
            'new_product_ids' => [],
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $mode = (string) ($data['taxonomy_mode'] ?? self::TAXONOMY_CATEGORIES);

        return [
            'taxonomy_mode' => in_array($mode, [self::TAXONOMY_CATEGORIES, self::TAXONOMY_PRODUCT_FAMILIES], true)
                ? $mode
                : self::TAXONOMY_CATEGORIES,
            'new_products_enabled' => (bool) ($data['new_products_enabled'] ?? true),
            'new_product_ids' => $this->parseProductIds($data['new_product_ids'] ?? []),
        ];
    }

    /** @return list<int> */
    protected function parseProductIds(mixed $raw): array
    {
        if (is_array($raw)) {
            return collect($raw)
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        $text = trim((string) $raw);
        if ($text === '') {
            return [];
        }

        $text = str_replace(['،', ';', "\n", "\r", "\t"], ',', $text);

        return collect(explode(',', $text))
            ->map(fn (string $part) => (int) trim($part))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** @param  list<int>  $ids */
    protected function formatIdsForAdmin(array $ids): string
    {
        if ($ids === []) {
            return '';
        }

        return implode(', ', $ids);
    }
}
