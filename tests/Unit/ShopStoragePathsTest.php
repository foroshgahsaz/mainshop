<?php

namespace Tests\Unit;

use App\Support\ShopStoragePaths;
use Tests\TestCase;

class ShopStoragePathsTest extends TestCase
{
    public function test_logs_directory_defaults_to_storage_logs(): void
    {
        config(['shop.storage.logs_directory' => null]);

        $this->assertSame(storage_path('logs'), ShopStoragePaths::logsDirectory());
    }

    public function test_logs_directory_honors_config(): void
    {
        config(['shop.storage.logs_directory' => '/data/logs']);

        $this->assertSame('/data/logs', ShopStoragePaths::logsDirectory());
        $this->assertSame('/data/logs/laravel.log', ShopStoragePaths::laravelLogPath());
    }

    public function test_logs_directory_from_environment_when_shop_config_not_loaded_yet(): void
    {
        config(['shop.storage.logs_directory' => null]);

        $this->app->loadEnvironmentFrom('.env.testing');

        putenv('FILESYSTEM_PUBLIC_ROOT=/data');
        putenv('SHOP_LOGS_DIRECTORY=');

        $this->assertSame('/data/logs', ShopStoragePaths::logsDirectoryFromEnvironment());

        putenv('FILESYSTEM_PUBLIC_ROOT');
    }
}
