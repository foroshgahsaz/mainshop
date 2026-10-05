<?php

namespace App\Services\Settings;

use Illuminate\Support\Facades\Cache;

class SiteHeaderSettingsService
{
    public const SETTINGS_GROUP = 'site_header';

    protected const CACHE_KEY = 'shop:site_header:settings:all';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array{snippets: list<array{label: string, enabled: bool, defer: bool, code: string}>} */
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

    /**
     * Snippets that should be output in the shop layout &lt;head&gt;.
     *
     * @return list<array{label: string, enabled: bool, defer: bool, code: string}>
     */
    public function activeSnippets(): array
    {
        return collect($this->all()['snippets'] ?? [])
            ->filter(fn (array $item): bool => ($item['enabled'] ?? false) && trim((string) ($item['code'] ?? '')) !== '')
            ->values()
            ->all();
    }

    /** @return array{snippets: list<array<string, mixed>>} */
    public function forAdminForm(): array
    {
        return [
            'snippets' => $this->all()['snippets'],
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function saveFromAdminForm(array $data): void
    {
        $normalized = $this->normalize([
            'snippets' => $data['snippets'] ?? [],
        ]);

        Cache::forget(self::CACHE_KEY);

        $this->settings->set(
            self::SETTINGS_GROUP,
            'data',
            json_encode($normalized, JSON_UNESCAPED_UNICODE)
        );
    }

    /** @return array{snippets: list<array{label: string, enabled: bool, defer: bool, code: string}>} */
    protected function defaults(): array
    {
        return [
            'snippets' => [],
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function normalize(array $data): array
    {
        $snippets = collect($data['snippets'] ?? [])
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'label' => trim((string) ($item['label'] ?? '')),
                'enabled' => (bool) ($item['enabled'] ?? true),
                'defer' => (bool) ($item['defer'] ?? false),
                'code' => trim((string) ($item['code'] ?? '')),
            ])
            ->filter(fn (array $item): bool => $item['code'] !== '' || $item['label'] !== '')
            ->values()
            ->all();

        return [
            'snippets' => $snippets,
        ];
    }
}
