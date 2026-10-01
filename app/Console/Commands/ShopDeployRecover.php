<?php

namespace App\Console\Commands;

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
        $this->components->info('Clearing cached config, routes, views, and application cache…');

        Artisan::call('optimize:clear');
        $this->line(trim(Artisan::output()));

        $this->components->info('Running migrations…');
        Artisan::call('migrate', ['--force' => true]);
        $this->line(trim(Artisan::output()));

        if (! $this->option('optimize')) {
            $this->components->warn('Skipped config/route/view cache. Run with --optimize when Redis/cache is confirmed working.');

            return self::SUCCESS;
        }

        if (! $this->canUseRedis()) {
            $this->components->warn('Redis is not reachable. Keep CACHE_STORE=database and SESSION_DRIVER=database in .env, then run without --optimize or fix Redis first.');

            return self::SUCCESS;
        }

        $this->components->info('Caching config, routes, and views…');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->components->info('Deploy recovery finished.');

        return self::SUCCESS;
    }

    protected function canUseRedis(): bool
    {
        if (config('cache.default') !== 'redis' && config('session.driver') !== 'redis') {
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
