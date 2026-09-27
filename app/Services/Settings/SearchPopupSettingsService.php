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
            'popular_searches' => $data['popular_searches'],
            'show_categories' => $data['show_categories'],
            'banner' => $data['banner'],
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function saveFromAdminForm(array $data): void
    {
        $normalized = $this->normalize([
            'placeholder' => $data['placeholder'] ?? '',
            'popular_searches' => $data['popular_searches'] ?? [],
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
        return collect($this->all()['popular_searches'] ?? [])
            ->pluck('term')
            ->filter()
            ->values()
            ->all();
    }

    /** @return list<array{term: string, url: string}> */
    public function popularSearchLinks(): array
    {
        return collect($this->all()['popular_searches'] ?? [])
            ->map(function (array $row): array {
                $term = (string) ($row['term'] ?? '');
                $link = trim((string) ($row['link'] ?? ''));

                return [
                    'term' => $term,
                    'url' => $this->resolvePopularLink($term, $link),
                ];
            })
            ->filter(fn (array $row): bool => $row['term'] !== '')
            ->values()
            ->all();
    }

    protected function resolvePopularLink(string $term, string $link): string
    {
        if ($link === '') {
            return route('products.index', ['search' => $term]);
        }

        if (str_starts_with($link, '/') && ! str_starts_with($link, '//')) {
            return url($link);
        }

        return $link;
    }

    /** @return array<string, mixed> */
    protected function defaults(): array
    {
        return [
            'placeholder' => 'جستجو در تمام محصولات '.site_name().'...',
            'popular_searches' => [
                ['term' => 'پیراهن', 'link' => ''],
                ['term' => 'کفش', 'link' => ''],
                ['term' => 'پوشاک', 'link' => ''],
                ['term' => site_name(), 'link' => route('products.index')],
            ],
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
            'popular_searches' => $this->normalizePopularSearches($data['popular_searches'] ?? []),
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

    /** @return list<array{term: string, link: string}> */
    protected function normalizePopularSearches(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (is_string($row)) {
                $term = trim($row);
                if ($term !== '') {
                    $normalized[] = ['term' => $term, 'link' => ''];
                }

                continue;
            }

            if (! is_array($row)) {
                continue;
            }

            $term = trim((string) ($row['term'] ?? ''));
            if ($term === '') {
                continue;
            }

            $normalized[] = [
                'term' => $term,
                'link' => trim((string) ($row['link'] ?? '')),
            ];
        }

        return $normalized;
    }
}
