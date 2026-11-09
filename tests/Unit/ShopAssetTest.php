<?php

namespace Tests\Unit;

use App\Services\Settings\ShopAssetSettingsService;
use App\Services\Settings\SettingsService;
use App\Support\ShopAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_serves_original_when_minify_disabled(): void
    {
        $this->assertSame('shop/css/custom.css', ShopAsset::resolvePath('shop/css/custom.css', 'custom_css'));
    }

    public function test_serves_min_when_enabled_and_file_exists_in_storage(): void
    {
        Storage::fake('public');

        $relative = 'shop/css/custom.css';
        $minRelative = 'shop/css/custom.min.css';
        $dir = public_path('shop/css');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(public_path($relative), 'body { color: red; }');
        Storage::disk('public')->put(ShopAsset::minStorageRelativePath($relative), 'body{color:red}');

        app(SettingsService::class)->set(ShopAssetSettingsService::SETTINGS_GROUP, 'data', json_encode([
            'enabled' => true,
            'serve' => ['custom_css' => true],
        ], JSON_UNESCAPED_UNICODE));
        app(ShopAssetSettingsService::class)->forgetCache();

        $this->assertSame($minRelative, ShopAsset::resolvePath($relative, 'custom_css'));
        $url = ShopAsset::url($relative, 'custom_css');
        $this->assertStringContainsString('shop-min/css/custom.min.css', $url);
        $this->assertStringContainsString('/data/', $url);

        @unlink(public_path($relative));
    }

    public function test_falls_back_to_original_when_min_missing(): void
    {
        app(SettingsService::class)->set(ShopAssetSettingsService::SETTINGS_GROUP, 'data', json_encode([
            'enabled' => true,
            'serve' => ['main_js' => true],
        ], JSON_UNESCAPED_UNICODE));
        app(ShopAssetSettingsService::class)->forgetCache();

        $this->assertSame('shop/js/main.js', ShopAsset::resolvePath('shop/js/main.js', 'main_js'));
    }

    public function test_legacy_public_min_file_still_works(): void
    {
        $relative = 'shop/css/custom.css';
        $minRelative = 'shop/css/custom.min.css';
        $dir = public_path('shop/css');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(public_path($relative), 'body { color: red; }');
        file_put_contents(public_path($minRelative), 'body{color:red}');

        app(SettingsService::class)->set(ShopAssetSettingsService::SETTINGS_GROUP, 'data', json_encode([
            'enabled' => true,
            'serve' => ['custom_css' => true],
        ], JSON_UNESCAPED_UNICODE));
        app(ShopAssetSettingsService::class)->forgetCache();

        $this->assertTrue(ShopAsset::minifiedFileExists($relative));
        $this->assertStringContainsString('shop/css/custom.min.css', ShopAsset::url($relative, 'custom_css'));

        @unlink(public_path($relative));
        @unlink(public_path($minRelative));
    }
}
