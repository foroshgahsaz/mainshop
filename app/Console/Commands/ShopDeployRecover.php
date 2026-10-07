<?php

namespace App\Console\Commands;

use App\Support\ShopStoragePaths;
use App\Support\StoragePermissionFixer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redis;
use Throwable;

class ShopDeployRecover extends Command
{
    protected $signature = 'shop:deploy-recover {--optimize : Rebuild config/route/view cache after checks}';

    protected $description = 'Clear broken caches after deploy and run migrations (fixes common HTTP 500)';

    public function handle(): int
    {
        $this->ensurePersistentStorageDirectories();

        $this->components->info('Clearing cached config, routes, views, and application cache…');

        $this->useDatabaseCacheIfRedisIsDown();

        Artisan::call('optimize:clear');
        $this->line(trim(Artisan::output()));

        $this->components->info('Running migrations…');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(trim(Artisan::output()));

        if (array_key_exists('payments:archive-logs', Artisan::all())) {
            Artisan::call('payments:archive-logs');
            $this->line(trim(Artisan::output()));
        } else {
            $this->components->warn('payments:archive-logs is missing — deploy the latest application image/code (master after PR #150).');
        }

        if (! $this->option('optimize')) {
            $this->warnIfRedisMisconfigured();

            return self::SUCCESS;
        }

        if (! $this->canUseRedis()) {
            $this->components->warn('Redis is not reachable. Skipping config/route/view cache. Use database drivers in .env or enable Redis on the platform.');

            return self::SUCCESS;
        }

        $this->components->info('Caching config, routes, and views…');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->components->info('Deploy recovery finished.');

        return self::SUCCESS;
    }

    protected function ensurePersistentStorageDirectories(): void
    {
        foreach (ShopStoragePaths::persistentDirectories() as $directory) {
            ShopStoragePaths::ensureDirectory($directory);
        }

        StoragePermissionFixer::fix();
    }

    protected function useDatabaseCacheIfRedisIsDown(): void
    {
        if ($this->canUseRedis()) {
            return;
        }

        config([
            'cache.default' => 'database',
            'session.driver' => 'database',
        ]);
    }

    protected function warnIfRedisMisconfigured(): void
    {
        if ($this->canUseRedis()) {
            return;
        }

        $this->newLine();
        $this->components->warn('Redis is configured in .env but not reachable (Connection refused).');
        $this->line('  • Either enable/link Redis on Liara and set REDIS_HOST / REDIS_PASSWORD');
        $this->line('  • Or set CACHE_STORE=database and SESSION_DRIVER=database in environment variables');
        $this->line('  The web app falls back to database when Redis fails, but artisan cache:clear needs a working store.');
    }

    protected function canUseRedis(): bool
    {
        $usesRedis = config('cache.default') === 'redis' || config('session.driver') === 'redis';

        if (! $usesRedis) {
            return true;
        }

        try {
            Redis::connection()->ping();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
