<?php

namespace App\Support;

use App\Services\Settings\ShopAssetSettingsService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

final class ShopAsset
{
    public const MIN_STORAGE_PREFIX = 'shop-min';

    /**
     * آدرس فایل استاتیک فروشگاه با نسخه؛ در صورت تنظیم ادمین و وجود .min همان سرو می‌شود.
     */
    public static function url(string $publicPath, ?string $assetKey = null): string
    {
        $publicPath = ltrim(str_replace('\\', '/', $publicPath), '/');

        $key = $assetKey ?? self::keyForPath($publicPath);
        $serveMin = $key !== null
            && app(ShopAssetSettingsService::class)->shouldServeMinified($key)
            && self::minifiedFileExists($publicPath);

        $version = (int) config('shop.assets.css_version', 1);

        if ($serveMin) {
            $storageRelative = self::minStorageRelativePath($publicPath);

            if (Storage::disk('public')->exists($storageRelative)) {
                $mtime = (int) Storage::disk('public')->lastModified($storageRelative);

                return self::publicDiskUrl($storageRelative).'?v='.$version.'-'.$mtime;
            }

            $minPublic = self::minPathFor($publicPath);
            $fullPath = public_path($minPublic);

            if (is_file($fullPath)) {
                $mtime = (int) filemtime($fullPath);

                return asset($minPublic).'?v='.$version.'-'.$mtime;
            }
        }

        $fullPath = public_path($publicPath);
        $mtime = is_file($fullPath) ? (int) filemtime($fullPath) : 1;

        return asset($publicPath).'?v='.$version.'-'.$mtime;
    }

    public static function resolvePath(string $publicPath, ?string $assetKey = null): string
    {
        $key = $assetKey ?? self::keyForPath($publicPath);

        if ($key !== null && app(ShopAssetSettingsService::class)->shouldServeMinified($key)) {
            if (self::minifiedFileExists($publicPath)) {
                return self::minPathFor($publicPath);
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

    /** مسیر روی دیسک public (storage/app/public) برای فایل min ساخته‌شده از ادمین. */
    public static function minStorageRelativePath(string $publicPath): string
    {
        $minPublic = self::minPathFor($publicPath);
        $suffix = preg_replace('#^shop/#', '', $minPublic) ?? $minPublic;

        return self::MIN_STORAGE_PREFIX.'/'.$suffix;
    }

    public static function minifiedFileExists(string $publicPath): bool
    {
        if (Storage::disk('public')->exists(self::minStorageRelativePath($publicPath))) {
            return true;
        }

        return is_file(public_path(self::minPathFor($publicPath)));
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

    /**
     * URL عمومی فایل روی دیسک public — روی Runflare/Liara با route `/data/{path}` (نه `/storage/...`).
     */
    public static function publicDiskUrl(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        if (Route::has('filesystem.public')) {
            return route('filesystem.public', ['path' => $relativePath]);
        }

        return Storage::disk('public')->url($relativePath);
    }
}
