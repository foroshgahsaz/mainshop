<?php

namespace App\Support;

/**
 * Filament's bootstrap/cache/filament/panels/admin.php skips discoverResources().
 * After a new Resource is deployed, that stale file 404s the new URL until caches are cleared.
 */
class FilamentAdminComponentCache
{
    public static function forgetIfStale(): bool
    {
        $cachePath = self::panelCachePath();

        if (! is_file($cachePath)) {
            return false;
        }

        $cached = @include $cachePath;
        if (! is_array($cached)) {
            self::forgetPanelAndRouteCaches($cachePath);

            return true;
        }

        $cachedClasses = [];
        foreach ($cached['resources'] ?? [] as $class) {
            if (is_string($class) && $class !== '') {
                $cachedClasses[] = $class;
            }
        }

        foreach (glob(app_path('Filament/Resources/*.php')) ?: [] as $file) {
            $class = 'App\\Filament\\Resources\\'.basename($file, '.php');
            if (! in_array($class, $cachedClasses, true)) {
                self::forgetPanelAndRouteCaches($cachePath);

                return true;
            }
        }

        return false;
    }

    public static function panelCachePath(): string
    {
        $root = config('filament.cache_path') ?: base_path('bootstrap/cache/filament');

        return $root.DIRECTORY_SEPARATOR.'panels'.DIRECTORY_SEPARATOR.'admin.php';
    }

    protected static function forgetPanelAndRouteCaches(string $panelCachePath): void
    {
        self::deleteFile($panelCachePath);

        $routes = app()->getCachedRoutesPath();
        if (is_file($routes)) {
            self::deleteFile($routes);
        }
    }

    protected static function deleteFile(string $path): void
    {
        @unlink($path);
    }
}
