<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Shop\CategoryTaxonomySeederService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedCategoryTaxonomyCommand extends Command
{
    protected $signature = 'shop:seed-category-taxonomy
                            {--dry-run : فقط گزارش بدون تغییر DB}
                            {--force : بدون تأیید تعاملی (الزامی روی production)}';

    protected $description = 'حذف دسته‌های قبلی و ساخت درخت دسته‌بندی + مگا منو (لوازم خانگی، چینی/بلور، …)';

    public function handle(CategoryTaxonomySeederService $service): int
    {
        if (! $this->option('force') && ! $this->option('dry-run') && ! $this->option('no-interaction')) {
            if (! $this->confirm('همهٔ دسته‌های فعلی حذف و درخت جدید جایگزین می‌شود. ادامه؟', false)) {
                $this->components->warn('لغو شد.');

                return self::SUCCESS;
            }
        }

        if (! $this->option('dry-run') && ! $this->option('force') && $this->option('no-interaction')) {
            $this->components->error('روی سرور از --force استفاده کنید.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $service->run($dryRun);

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->table(
            ['شاخص', 'مقدار'],
            [
                ['دستهٔ جدید', $prefix.$result['created']],
                ['دستهٔ حذف‌شده', $prefix.$result['deleted']],
                ['محصول با دستهٔ نگاشت‌شده', $prefix.$result['products_remapped']],
                ['لینک دستی مگا حذف‌شده', $prefix.$result['mega_links_removed']],
            ],
        );

        if ($result['roots'] !== []) {
            $this->line('ریشه‌های مگا منو: '.implode(', ', array_map(fn (int $id) => '#'.$id, $result['roots'])));
        }

        if ($dryRun) {
            $this->components->warn('dry-run — برای اعمال: php artisan shop:seed-category-taxonomy --force');

            return self::SUCCESS;
        }

        $this->syncHomepageHeroFromNewTree();

        Artisan::call('cache:clear');
        $this->components->info('درخت دسته‌بندی و مگا منو اعمال شد.');

        return self::SUCCESS;
    }

    protected function syncHomepageHeroFromNewTree(): void
    {
        $chineseRoot = Category::query()
            ->where('is_active', true)
            ->where('name', 'چینی ، بلور ، اپال')
            ->first();

        if ($chineseRoot === null) {
            return;
        }

        app(HomepageSettingsService::class)->mergeAndPersist([
            'taxonomy_mode' => HomepageSettingsService::TAXONOMY_CATEGORIES,
        ]);

        $this->line('تنظیمات صفحه اصلی (taxonomy) با درخت جدید همگام شد.');
    }
}
