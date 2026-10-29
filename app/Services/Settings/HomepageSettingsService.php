<?php

namespace App\Services\Settings;

use App\Models\Product;
use App\Models\ProductFamily;
use App\Services\Shop\ProductFamilyHomepagePopupService;
use App\Support\ShopMedia;
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
            'hero_sidebar_family_id' => $data['hero_sidebar_family_id'],
            'hero_sidebar_title' => $data['hero_sidebar_title'],
            'hero_sidebar_links' => $data['hero_sidebar_links'],
            'hero_quick_family_ids' => $data['hero_quick_family_ids'],
            'new_products_enabled' => $data['new_products_enabled'],
            'new_product_ids' => $this->formatIdsForAdmin($data['new_product_ids']),
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function saveFromAdminForm(array $data): void
    {
        $normalized = $this->normalize([
            'taxonomy_mode' => $data['taxonomy_mode'] ?? self::TAXONOMY_CATEGORIES,
            'hero_sidebar_family_id' => $data['hero_sidebar_family_id'] ?? null,
            'hero_sidebar_title' => $data['hero_sidebar_title'] ?? 'سرویس چینی',
            'hero_sidebar_links' => $data['hero_sidebar_links'] ?? [],
            'hero_quick_family_ids' => $data['hero_quick_family_ids'] ?? [],
            'new_products_enabled' => $data['new_products_enabled'] ?? true,
            'new_product_ids' => $data['new_product_ids'] ?? '',
        ]);

        $normalized = $this->fillHeroSidebarLinksFromFamilyIfEmpty($normalized);

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

    public function heroSidebarFamilyId(): ?int
    {
        $id = (int) ($this->all()['hero_sidebar_family_id'] ?? 0);

        return $id > 0 ? $id : null;
    }

    public function heroSidebarTitle(): string
    {
        $title = trim((string) ($this->all()['hero_sidebar_title'] ?? ''));

        return $title !== '' ? $title : 'سرویس چینی';
    }

    /** @return list<array{label: string, url: string, image: ?string}> */
    public function heroSidebarLinks(): array
    {
        return array_map(
            fn (array $link): array => $this->presentHeroSidebarLink($link),
            $this->all()['hero_sidebar_links']
        );
    }

    /** @param  array{label: string, url: string, image?: ?string}  $link
     * @return array{label: string, url: string, image: ?string}
     */
    protected function presentHeroSidebarLink(array $link): array
    {
        $path = trim((string) ($link['image'] ?? ''));

        $imageUrl = null;
        if ($path !== '') {
            $imageUrl = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
                ? $path
                : ShopMedia::url($path);
        }

        return [
            'label' => (string) $link['label'],
            'url' => (string) $link['url'],
            'image' => $imageUrl,
        ];
    }

    /** Persist sidebar links from the product family when JSON has none (post-deploy backfill). */
    public function materializeHeroSidebarLinksIfEmpty(): bool
    {
        if ($this->heroSidebarLinks() !== []) {
            return false;
        }

        $filled = $this->fillHeroSidebarLinksFromFamilyIfEmpty($this->all());
        if (($filled['hero_sidebar_links'] ?? []) === []) {
            return false;
        }

        $this->settings->set(
            self::SETTINGS_GROUP,
            'data',
            json_encode($this->normalize($filled), JSON_UNESCAPED_UNICODE)
        );

        return true;
    }

    /**
     * @param  list<array{label: string, url: string}>  $links
     */
    public function syncHeroSidebarFromFamily(int $familyId, string $familyName, array $links): void
    {
        $payload = $this->normalize([
            ...$this->all(),
            'hero_sidebar_family_id' => $familyId,
            'hero_sidebar_title' => $familyName,
            'hero_sidebar_links' => $links,
        ]);

        $this->settings->set(
            self::SETTINGS_GROUP,
            'data',
            json_encode($payload, JSON_UNESCAPED_UNICODE)
        );
    }

    /** @return list<int> */
    public function heroQuickFamilyIds(): array
    {
        $raw = $this->all()['hero_quick_family_ids'] ?? [];

        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(3)
            ->values()
            ->all();
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
            'hero_sidebar_family_id' => null,
            'hero_sidebar_title' => 'سرویس چینی',
            'hero_sidebar_links' => [],
            'hero_quick_family_ids' => [],
            'new_products_enabled' => true,
            'new_product_ids' => [],
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $mode = (string) ($data['taxonomy_mode'] ?? self::TAXONOMY_CATEGORIES);

        $quickIds = $data['hero_quick_family_ids'] ?? [];
        if (! is_array($quickIds)) {
            $quickIds = [];
        }

        return [
            'taxonomy_mode' => in_array($mode, [self::TAXONOMY_CATEGORIES, self::TAXONOMY_PRODUCT_FAMILIES], true)
                ? $mode
                : self::TAXONOMY_CATEGORIES,
            'hero_sidebar_family_id' => $this->normalizeOptionalFamilyId($data['hero_sidebar_family_id'] ?? null),
            'hero_sidebar_title' => trim((string) ($data['hero_sidebar_title'] ?? 'سرویس چینی')) ?: 'سرویس چینی',
            'hero_sidebar_links' => $this->normalizeHeroSidebarLinks($data['hero_sidebar_links'] ?? []),
            'hero_quick_family_ids' => collect($quickIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->take(3)
                ->values()
                ->all(),
            'new_products_enabled' => (bool) ($data['new_products_enabled'] ?? true),
            'new_product_ids' => $this->parseProductIds($data['new_product_ids'] ?? []),
        ];
    }

    protected function normalizeOptionalFamilyId(mixed $raw): ?int
    {
        $id = (int) $raw;

        return $id > 0 ? $id : null;
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    protected function normalizeHeroSidebarLinks(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $links = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }

            $active = array_key_exists('is_active', $row)
                ? filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN)
                : true;
            if (! $active) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $url = trim((string) ($row['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }

            $image = trim((string) ($row['image'] ?? ''));

            $entry = [
                'label' => $label,
                'url' => $url,
            ];
            if ($image !== '') {
                $entry['image'] = $image;
            }
            $links[] = $entry;
        }

        return $links;
    }

    /** @param  array<string, mixed>  $normalized */
    protected function fillHeroSidebarLinksFromFamilyIfEmpty(array $normalized): array
    {
        if (($normalized['hero_sidebar_links'] ?? []) !== []) {
            return $normalized;
        }

        $familyId = $normalized['hero_sidebar_family_id'] ?? null;
        $family = null;

        if ($familyId !== null) {
            $family = ProductFamily::query()->whereKey($familyId)->where('is_active', true)->first();
        }

        if ($family === null) {
            $family = ProductFamily::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('slug', 'chinese-service')
                        ->orWhere('name', 'سرویس چینی');
                })
                ->orderBy('position')
                ->first();
        }

        if ($family === null) {
            return $normalized;
        }

        $popup = app(ProductFamilyHomepagePopupService::class);
        $links = $popup->storedLinkItemsForFamily($family);
        if ($links === []) {
            $links = [$popup->familyAsSidebarLink($family)];
            $links[0]['image'] = trim((string) ($family->image ?? '')) ?: null;
        }

        $normalized['hero_sidebar_family_id'] = (int) $family->id;
        $normalized['hero_sidebar_title'] = (string) $family->name;
        $normalized['hero_sidebar_links'] = $links;

        return $normalized;
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
