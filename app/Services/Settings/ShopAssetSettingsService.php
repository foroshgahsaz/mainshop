<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\Cache;

class ShopAssetSettingsService
{
    public const SETTINGS_GROUP = 'shop_assets';

    protected const CACHE_KEY = 'shop:shop_assets:settings:all';

    /** @var array<string, array{path: string, type: 'css'|'js', label: string}> */
    public const ASSET_DEFINITIONS = [
        'tailwind_css' => [
            'path' => 'shop/css/tailwind.css',
            'type' => 'css',
            'label' => 'CSS پایه (Tailwind)',
        ],
        'custom_css' => [
            'path' => 'shop/css/custom.css',
            'type' => 'css',
            'label' => 'CSS سفارشی فروشگاه',
        ],
        'rich_content_css' => [
            'path' => 'shop/css/rich-content.css',
            'type' => 'css',
            'label' => 'CSS محتوای غنی',
        ],
        'listing_css' => [
            'path' => 'shop/css/listing.css',
            'type' => 'css',
            'label' => 'CSS لیست محصولات',
        ],
        'main_js' => [
            'path' => 'shop/js/main.js',
            'type' => 'js',
            'label' => 'JavaScript اصلی (main.js)',
        ],
        'listing_filters_js' => [
            'path' => 'shop/js/listing-filters.js',
            'type' => 'js',
            'label' => 'JavaScript فیلتر لیست',
        ],
    ];

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array{enabled: bool, serve: array<string, bool>} */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
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
        });
    }

    public function enabled(): bool
    {
        return (bool) ($this->all()['enabled'] ?? false);
    }

    public function shouldServeMinified(string $assetKey): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return (bool) ($this->all()['serve'][$assetKey] ?? false);
    }

    /** @return array{enabled: bool, serve: array<string, bool>} */
    public function forAdminForm(): array
    {
        return $this->all();
    }

    /** @param  array<string, mixed>  $data */
    public function saveFromAdminForm(array $data): void
    {
        $normalized = $this->normalize($data);
        $this->settings->set(self::SETTINGS_GROUP, 'data', json_encode($normalized, JSON_UNESCAPED_UNICODE));
        Cache::forget(self::CACHE_KEY);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array{enabled: bool, serve: array<string, bool>} */
    protected function defaults(): array
    {
        $serve = [];
        foreach (array_keys(self::ASSET_DEFINITIONS) as $key) {
            $serve[$key] = false;
        }

        return [
            'enabled' => false,
            'serve' => $serve,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{enabled: bool, serve: array<string, bool>}
     */
    protected function normalize(array $data): array
    {
        $defaults = $this->defaults();

        $serve = $defaults['serve'];
        foreach (array_keys(self::ASSET_DEFINITIONS) as $key) {
            $serve[$key] = (bool) ($data['serve'][$key] ?? $serve[$key] ?? false);
        }

        return [
            'enabled' => (bool) ($data['enabled'] ?? $defaults['enabled']),
            'serve' => $serve,
        ];
    }
}
