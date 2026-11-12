<?php

namespace Tests\Unit;

use App\Support\FilamentAdminComponentCache;
use Tests\TestCase;

class FilamentAdminComponentCacheTest extends TestCase
{
    private ?string $backupPanelCache = null;

    private ?string $backupRoutesCache = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupPanelCache = $this->backupFile(FilamentAdminComponentCache::panelCachePath());
        $this->backupRoutesCache = $this->backupFile(app()->getCachedRoutesPath());
    }

    protected function tearDown(): void
    {
        $this->restoreFile(FilamentAdminComponentCache::panelCachePath(), $this->backupPanelCache);
        $this->restoreFile(app()->getCachedRoutesPath(), $this->backupRoutesCache);

        parent::tearDown();
    }

    public function test_stale_panel_cache_without_otp_resource_is_deleted(): void
    {
        $panelPath = FilamentAdminComponentCache::panelCachePath();
        $this->writePhpArray($panelPath, [
            'resources' => ['App\\Filament\\Resources\\UserResource'],
        ]);

        $routesPath = app()->getCachedRoutesPath();
        $this->ensureDirectory($routesPath);
        file_put_contents($routesPath, '<?php return [];');

        $this->assertTrue(FilamentAdminComponentCache::forgetIfStale());
        $this->assertFileDoesNotExist($panelPath);
        $this->assertFileDoesNotExist($routesPath);
    }

    public function test_panel_cache_covering_all_resources_is_kept(): void
    {
        $resources = [];
        foreach (glob(app_path('Filament/Resources/*.php')) ?: [] as $file) {
            $resources[] = 'App\\Filament\\Resources\\'.basename($file, '.php');
        }

        $this->assertContains('App\\Filament\\Resources\\OtpMessageResource', $resources);

        $panelPath = FilamentAdminComponentCache::panelCachePath();
        $this->writePhpArray($panelPath, ['resources' => $resources]);

        $this->assertFalse(FilamentAdminComponentCache::forgetIfStale());
        $this->assertFileExists($panelPath);
    }

    private function writePhpArray(string $path, array $value): void
    {
        $this->ensureDirectory($path);
        file_put_contents($path, '<?php return '.var_export($value, true).';');
    }

    private function ensureDirectory(string $filePath): void
    {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private function backupFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $copy = $path.'.testbak';
        copy($path, $copy);

        return $copy;
    }

    private function restoreFile(string $path, ?string $backup): void
    {
        if (is_file($path)) {
            @unlink($path);
        }

        if ($backup !== null && is_file($backup)) {
            rename($backup, $path);
        }
    }
}
