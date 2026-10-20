<?php

use App\Models\Setting;
use App\Services\Settings\FooterSettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $row = Setting::query()
            ->where('group', FooterSettingsService::SETTINGS_GROUP)
            ->where('key', 'data')
            ->first();

        if ($row === null || ! is_string($row->value) || $row->value === '') {
            return;
        }

        $decoded = json_decode($row->value, true);

        if (! is_array($decoded)) {
            return;
        }

        $decoded['about_paragraphs'] = [];

        $genericBrand = 'کالای دیجیتال، لوازم خانگی، پوشاک و دوره‌های آموزشی';
        $brand = trim((string) ($decoded['brand_description'] ?? ''));

        if ($brand !== '' && str_contains($brand, $genericBrand)) {
            $decoded['brand_description'] = '';
        }

        $row->value = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        $row->save();

        Cache::forget('shop:footer:settings:all');
        Cache::forget('shop:settings:all');
    }

    public function down(): void
    {
        // one-way cleanup of demo copy
    }
};
