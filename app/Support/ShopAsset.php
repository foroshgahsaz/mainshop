<?php

namespace App\Support;

use App\Services\Settings\ShopAssetSettingsService;

final class ShopAsset
{
    /**
     * آدرس فایل استاتیک فروشگاه با نسخه؛ در صورت تنظیم ادمین و وجود .min همان سرو می‌شود.
     */
    public static function url(string $publicPath, ?string $assetKey = null): string
    {
        $publicPath = ltrim(str_replace('\\', '/', $publicPath), '/');

        $resolved = self::resolvePath($publicPath, $assetKey);
        $fullPath = public_path($resolved);

        $version = (int) config('shop.assets.css_version', 1);
        $mtime = is_file($fullPath) ? (int) filemtime($fullPath) : 1;

        return asset($resolved).'?v='.$version.'-'.$mtime;
    }

    public static function resolvePath(string $publicPath, ?string $assetKey = null): string
    {
        $key = $assetKey ?? self::keyForPath($publicPath);

        if ($key !== null && app(ShopAssetSettingsService::class)->shouldServeMinified($key)) {
            $minPath = self::minPathFor($publicPath);
            if (is_file(public_path($minPath))) {
                return $minPath;
            }
        }

        return $publicPath;
    }

    public static function minPathFor(string $publicPath): string
    {
        if (preg_match('/\.(css|js)$/i', $publicPath) === 1) {
            return preg_replace('/\.(css|js)$/i', '.min.$1', $publicPath) ?? $publicPath;
        }

        return $publicPath;
    }

    public static function keyForPath(string $publicPath): ?string
    {
        foreach (ShopAssetSettingsService::ASSET_DEFINITIONS as $key => $definition) {
            if ($definition['path'] === $publicPath) {
                return $key;
            }
        }

        return null;
    }
}
