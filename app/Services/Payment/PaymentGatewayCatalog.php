<?php

namespace App\Services\Payment;

use App\Services\Settings\SettingsService;
use RuntimeException;

class PaymentGatewayCatalog
{
    public const TYPE_CASH = 'cash';

    public const TYPE_CREDIT = 'credit';

    public function __construct(
        protected SettingsService $settings,
    ) {}

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $gateways = [];

        foreach (array_keys(config('payment.gateways', [])) as $name) {
            $gateways[$name] = $this->definition($name);
        }

        return $gateways;
    }

    /** @return array<string, mixed> */
    public function definition(string $name): array
    {
        $config = config("payment.gateways.{$name}", []);

        return [
            'name' => $name,
            'type' => $config['type'] ?? self::TYPE_CASH,
            'label' => $config['label'] ?? $name,
            'description' => $config['description'] ?? '',
            'enabled' => $this->isEnabled($name),
        ];
    }

    public function isEnabled(string $name): bool
    {
        return match ($name) {
            'zarinpal' => (bool) ($this->settings->zarinpal()['enabled'] ?? false),
            'tara' => (bool) ($this->settings->tara()['enabled'] ?? false),
            default => false,
        };
    }

    public function exists(string $name): bool
    {
        return is_string(config("payment.gateways.{$name}.driver"));
    }

    /** @return list<array<string, mixed>> */
    public function enabled(?string $type = null): array
    {
        $items = [];

        foreach ($this->all() as $gateway) {
            if (! $gateway['enabled']) {
                continue;
            }

            if ($type !== null && $gateway['type'] !== $type) {
                continue;
            }

            $items[] = $gateway;
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    public function cash(): array
    {
        return $this->enabled(self::TYPE_CASH);
    }

    /** @return list<array<string, mixed>> */
    public function credit(): array
    {
        return $this->enabled(self::TYPE_CREDIT);
    }

    /** @return list<string> */
    public function enabledNames(): array
    {
        return array_column($this->enabled(), 'name');
    }

    public function assertEnabled(string $name): void
    {
        if (! $this->exists($name)) {
            throw new RuntimeException('درگاه پرداخت پشتیبانی نمی‌شود.');
        }

        if (! $this->isEnabled($name)) {
            throw new RuntimeException('این درگاه پرداخت فعال نیست.');
        }
    }
}
