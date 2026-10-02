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

    public function staffPhonesRaw(): string
    {
        $stored = $this->settings->get(self::SETTINGS_GROUP, 'staff_phones');

        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        return (string) config('transactional-sms.staff_phones_default', '');
    }

    /** @return list<string> */
    public function staffPhoneList(): array
    {
        $raw = $this->staffPhonesRaw();
        $parts = preg_split('/[\s,;]+/u', $raw) ?: [];

        return collect($parts)
            ->map(fn (string $phone) => preg_replace('/\D+/', '', $phone) ?? '')
            ->filter(fn (string $phone) => preg_match('/^09\d{9}$/', $phone))
            ->unique()
            ->values()
            ->all();
    }

    public function saveStaffPhones(string $raw): void
    {
        $this->settings->set(self::SETTINGS_GROUP, 'staff_phones', trim($raw));
    }

    /** @param  list<array{key: string, enabled?: bool, body?: string}>  $rows */
    public function saveFromAdminForm(array $rows, ?string $staffPhones = null): void
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

        if ($staffPhones !== null) {
            $this->saveStaffPhones($staffPhones);
        }
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
