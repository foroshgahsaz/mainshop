<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected const CACHE_KEY = 'shop:settings:all';

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return data_get($settings, "{$group}.{$key}", $default);
    }

    public function set(string $group, string $key, mixed $value): void
    {
        Setting::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /** @param  array<string, mixed>  $values */
    public function setMany(string $group, array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($group, $key, $value);
        }
    }

    /** @return array<string, array<string, string|null>> */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            return Setting::query()
                ->get()
                ->groupBy('group')
                ->map(fn ($items) => $items->pluck('value', 'key')->all())
                ->all();
        });
    }

    /** @return array<string, mixed> */
    public function zarinpal(): array
    {
        return [
            'merchant_id' => $this->get('zarinpal', 'merchant_id') ?: config('payment.gateways.zarinpal.merchant_id'),
            'sandbox' => filter_var(
                $this->get('zarinpal', 'sandbox', config('payment.gateways.zarinpal.sandbox')),
                FILTER_VALIDATE_BOOLEAN
            ),
            'callback_url' => $this->get('zarinpal', 'callback_url') ?: config('payment.gateways.zarinpal.callback_url'),
            'enabled' => filter_var($this->get('zarinpal', 'enabled', true), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /** @return array<string, mixed> */
    public function site(): array
    {
        return [
            'name' => $this->get('site', 'name') ?: config('app.name', 'چاپینو'),
            'description' => $this->get('site', 'description', ''),
            'logo' => $this->get('site', 'logo'),
            'favicon' => $this->get('site', 'favicon'),
            'phone' => $this->get('site', 'phone', ''),
            'email' => $this->get('site', 'email', ''),
            'address' => $this->get('site', 'address', ''),
            'instagram' => $this->get('site', 'instagram', ''),
            'telegram' => $this->get('site', 'telegram', ''),
        ];
    }

    /** @return array<string, mixed> */
    public function kavenegar(): array
    {
        return [
            'api_key' => $this->get('kavenegar', 'api_key') ?: config('sms.kavenegar.api_key'),
            'sender' => $this->get('kavenegar', 'sender') ?: config('sms.kavenegar.sender'),
            'otp_template' => $this->get('kavenegar', 'otp_template') ?: config('sms.kavenegar.otp_template'),
            'enabled' => filter_var($this->get('kavenegar', 'enabled', config('sms.driver') === 'kavenegar'), FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
