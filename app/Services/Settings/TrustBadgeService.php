<?php

namespace App\Services\Settings;

use Illuminate\Support\Str;

class TrustBadgeService
{
    public const SETTINGS_GROUP = 'trust_badges';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $stored = $this->settings->get(self::SETTINGS_GROUP, 'items');

        if (! is_string($stored) || $stored === '') {
            return [];
        }

        $decoded = json_decode($stored, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $item): ?array => $this->normalizeItem(is_array($item) ? $item : []),
            $decoded
        )));
    }

    /** @return array<int, array<string, mixed>> */
    public function active(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (array $badge): bool => (bool) ($badge['enabled'] ?? true)
                && (
                    ($badge['type'] === 'code' && filled($badge['code']))
                    || ($badge['type'] === 'image' && filled($badge['image']))
                )
        ));
    }

    /** @return array<int, array<string, mixed>> */
    public function forAdminForm(): array
    {
        return array_map(function (array $badge): array {
            return [
                'id' => $badge['id'],
                'title' => $badge['title'],
                'type' => $badge['type'],
                'code' => $badge['code'],
                'image' => filled($badge['image']) ? [$badge['image']] : [],
                'link' => $badge['link'],
                'enabled' => (bool) ($badge['enabled'] ?? true),
            ];
        }, $this->all());
    }

    /** @param  array<int, array<string, mixed>>  $items */
    public function saveFromAdminForm(array $items): void
    {
        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = in_array($item['type'] ?? '', ['code', 'image'], true)
                ? $item['type']
                : 'image';

            $image = collect($item['image'] ?? [])
                ->first(fn (mixed $value): bool => is_string($value) && $value !== '') ?? '';

            $normalized[] = [
                'id' => filled($item['id'] ?? null) ? (string) $item['id'] : (string) Str::ulid(),
                'title' => trim((string) ($item['title'] ?? '')),
                'type' => $type,
                'code' => $type === 'code' ? trim((string) ($item['code'] ?? '')) : '',
                'image' => $type === 'image' ? (string) $image : '',
                'link' => $type === 'image' ? trim((string) ($item['link'] ?? '')) : '',
                'enabled' => (bool) ($item['enabled'] ?? true),
            ];
        }

        $this->settings->set(
            self::SETTINGS_GROUP,
            'items',
            json_encode($normalized, JSON_UNESCAPED_UNICODE)
        );
    }

    /** @param  array<string, mixed>  $item */
    protected function normalizeItem(array $item): ?array
    {
        $type = in_array($item['type'] ?? '', ['code', 'image'], true)
            ? $item['type']
            : 'image';

        $title = trim((string) ($item['title'] ?? ''));
        $code = trim((string) ($item['code'] ?? ''));
        $image = trim((string) ($item['image'] ?? ''));
        $link = trim((string) ($item['link'] ?? ''));
        $enabled = (bool) ($item['enabled'] ?? true);

        if ($title === '' && $code === '' && $image === '') {
            return null;
        }

        return [
            'id' => filled($item['id'] ?? null) ? (string) $item['id'] : (string) Str::ulid(),
            'title' => $title,
            'type' => $type,
            'code' => $code,
            'image' => $image,
            'link' => $link,
            'enabled' => $enabled,
        ];
    }
}
