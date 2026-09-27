<?php

namespace App\Services\Settings;

class TransactionalSmsSettingsService
{
    public const SETTINGS_GROUP = 'transactional_sms';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<string, array{label: string, enabled: bool, body: string}> */
    public function all(): array
    {
        $defaults = config('transactional-sms.templates', []);
        $stored = $this->settings->get(self::SETTINGS_GROUP, 'templates');

        if (! is_string($stored) || $stored === '') {
            return $this->normalize($defaults);
        }

        $decoded = json_decode($stored, true);

        if (! is_array($decoded)) {
            return $this->normalize($defaults);
        }

        $merged = [];

        foreach ($defaults as $key => $default) {
            $merged[$key] = array_merge($default, is_array($decoded[$key] ?? null) ? $decoded[$key] : []);
        }

        return $this->normalize($merged);
    }

    /** @return array{enabled: bool, body: string}|null */
    public function template(string $key): ?array
    {
        $all = $this->all();
        $row = $all[$key] ?? null;

        if (! is_array($row)) {
            return null;
        }

        return [
            'enabled' => (bool) ($row['enabled'] ?? false),
            'body' => (string) ($row['body'] ?? ''),
        ];
    }

    /** @return list<array{key: string, label: string, enabled: bool, body: string}> */
    public function forAdminForm(): array
    {
        return collect($this->all())
            ->map(fn (array $row, string $key): array => [
                'key' => $key,
                'label' => (string) ($row['label'] ?? $key),
                'enabled' => (bool) ($row['enabled'] ?? true),
                'body' => (string) ($row['body'] ?? ''),
            ])
            ->values()
            ->all();
    }

    /** @param  list<array{key: string, enabled?: bool, body?: string}>  $rows */
    public function saveFromAdminForm(array $rows): void
    {
        $defaults = config('transactional-sms.templates', []);
        $templates = [];

        foreach ($rows as $row) {
            $key = (string) ($row['key'] ?? '');

            if ($key === '' || ! isset($defaults[$key])) {
                continue;
            }

            $templates[$key] = [
                'label' => (string) ($defaults[$key]['label'] ?? $key),
                'enabled' => (bool) ($row['enabled'] ?? true),
                'body' => trim((string) ($row['body'] ?? '')),
            ];
        }

        foreach ($defaults as $key => $default) {
            if (! isset($templates[$key])) {
                $templates[$key] = $default;
            }
        }

        $this->settings->set(
            self::SETTINGS_GROUP,
            'templates',
            json_encode($this->normalize($templates), JSON_UNESCAPED_UNICODE)
        );
    }

    /** @param  array<string, array<string, mixed>>  $templates */
    protected function normalize(array $templates): array
    {
        $normalized = [];

        foreach ($templates as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $body = trim((string) ($row['body'] ?? ''));
            $normalized[$key] = [
                'label' => (string) ($row['label'] ?? $key),
                'enabled' => (bool) ($row['enabled'] ?? true),
                'body' => $body !== '' ? $body : (string) (config("transactional-sms.templates.{$key}.body") ?? ''),
            ];
        }

        return $normalized;
    }
}
