<?php

namespace App\Console\Commands;

use App\Services\Cache\ShopCacheService;
use App\Services\Shop\CategoryMegaMenuVisibilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FinalizeCatalogCategoryOnlyCommand extends Command
{
    protected $signature = 'shop:finalize-catalog-category-only
                            {--dry-run : بدون ذخیره}
                            {--skip-mega-links : بدون migrate-mega-menu-roots}
                            {--skip-homepage : بدون migrate-homepage-settings-categories}';

    protected $description = 'مراحل باقی‌مانده مهاجرت کاتالوگ: لینkهای مگا، تنظیمات هیرو، show_in_mega زیرشاخه‌ها، پاکسازی کش';

    public function handle(
        CategoryMegaMenuVisibilityService $visibility,
        ShopCacheService $cache,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->components->warn('حالت dry-run — تغییری ذخیره نمی‌شود (به‌جز subcommandهای بدون dry-run).');
        }

        if (! $this->option('skip-mega-links')) {
            $this->components->info('مهاجرت لینک‌های مگا منو و ریشه‌ها…');
            $args = [
                '--enable-roots' => true,
                '--write-fk' => true,
            ];
            if ($dryRun) {
                $args['--dry-run'] = true;
            }
            Artisan::call('shop:migrate-mega-menu-roots', $args);
            $this->line(trim(Artisan::output()));
        }

        if (! $this->option('skip-homepage') && ! $dryRun) {
            $this->components->info('مهاجرت تنظیمات هیرو صفحه اصلی…');
            Artisan::call('shop:migrate-homepage-settings-categories');
            $this->line(trim(Artisan::output()));
        }

        $this->components->info('همگام‌سازی show_in_mega روی درخت مگا منو…');
        $result = $visibility->syncFromMappedFamilies($dryRun);
        $this->line(
            'ریشه‌ها: '.implode(', ', array_map(fn (int $id) => '#'.$id, $result['roots']))
            ." | دسته‌های روشن‌شده: {$result['categories_enabled']}"
        );

        if (! $dryRun) {
            $cache->forgetHome();
            $cache->forgetCategory();
            Artisan::call('cache:clear');
            $this->components->info('کش صفحه اصلی، دسته و اپلیکیشن پاک شد.');
        }

        $this->components->info('shop:finalize-catalog-category-only تمام شد.');

        return self::SUCCESS;
    }
}
