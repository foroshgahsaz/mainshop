<?php

namespace App\Services\Settings;

class SearchPopupSettingsService
{
    public const SETTINGS_GROUP = 'search_popup';

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
            'placeholder' => $data['placeholder'],
            'popular_searches' => collect($data['popular_searches'])
                ->map(fn (string $term): array => ['term' => $term])
                ->values()
                ->all(),
            'show_categories' => $data['show_categories'],
            'banner' => $data['banner'],
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function saveFromAdminForm(array $data): void
    {
        $normalized = $this->normalize([
            'placeholder' => $data['placeholder'] ?? '',
            'popular_searches' => collect($data['popular_searches'] ?? [])
                ->map(fn (mixed $row): string => is_array($row) ? trim((string) ($row['term'] ?? '')) : trim((string) $row))
                ->filter()
                ->values()
                ->all(),
            'show_categories' => $data['show_categories'] ?? true,
            'banner' => $data['banner'] ?? [],
        ]);

        $this->settings->set(
            self::SETTINGS_GROUP,
            'data',
            json_encode($normalized, JSON_UNESCAPED_UNICODE)
        );
    }

    /** @return list<string> */
    public function popularSearchTerms(): array
    {
        $terms = $this->all()['popular_searches'] ?? [];

        return array_values(array_filter($terms, fn ($t) => is_string($t) && $t !== ''));
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'placeholder' => 'جستجو در تمام محصولات '.site_name().'...',
            'popular_searches' => ['پیراهن', 'کفش', 'پوشاک', site_name()],
            'show_categories' => true,
            'banner' => [
                'enabled' => true,
                'image' => null,
                'title' => 'تخفیف‌های شگفت‌انگیز '.site_name(),
                'subtitle' => 'فروش ویژه',
                'link' => route('products.index'),
                'button_text' => 'مشاهده کالاها',
            ],
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $banner = is_array($data['banner'] ?? null) ? $data['banner'] : [];

        return [
            'placeholder' => trim((string) ($data['placeholder'] ?? '')),
            'popular_searches' => array_values(array_filter(
                array_map('strval', $data['popular_searches'] ?? []),
                fn (string $t) => $t !== ''
            )),
            'show_categories' => (bool) ($data['show_categories'] ?? true),
            'banner' => [
                'enabled' => (bool) ($banner['enabled'] ?? true),
                'image' => filled($banner['image'] ?? null) ? (string) $banner['image'] : null,
                'title' => trim((string) ($banner['title'] ?? '')),
                'subtitle' => trim((string) ($banner['subtitle'] ?? '')),
                'link' => trim((string) ($banner['link'] ?? '')),
                'button_text' => trim((string) ($banner['button_text'] ?? 'مشاهده')),
            ],
        ];
    }
}
