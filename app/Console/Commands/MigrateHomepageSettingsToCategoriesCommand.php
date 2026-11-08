<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Shop\CatalogFamilyCategoryMapper;
use Illuminate\Console\Command;

class MigrateHomepageSettingsToCategoriesCommand extends Command
{
    protected $signature = 'shop:migrate-homepage-settings-categories {--dry-run}';

    protected $description = 'تنظیمات هیرو صفحه اصلی: family_id → category_id و بازنویسی URLهای product_family_id';

    public function handle(
        HomepageSettingsService $homepage,
        ShopCacheService $cache,
        CatalogFamilyCategoryMapper $mapper,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $data = $homepage->all();
        $overrides = [];

        $familySidebar = $data['hero_sidebar_family_id'] ?? null;
        if (empty($data['hero_sidebar_category_id']) && $familySidebar) {
            $mapped = $mapper->rootCategoryIdForFamilyId((int) $familySidebar);
            if ($mapped !== null) {
                $overrides['hero_sidebar_category_id'] = $mapped;
                $name = Category::query()->whereKey($mapped)->value('name');
                if ($name) {
                    $overrides['hero_sidebar_title'] = $name;
                }
                $this->line("hero_sidebar_category_id ← #{$mapped}");
            }
        }

        $quickFamily = $data['hero_quick_family_ids'] ?? [];
        if (($data['hero_quick_category_ids'] ?? []) === [] && is_array($quickFamily) && $quickFamily !== []) {
            $overrides['hero_quick_category_ids'] = collect($quickFamily)
                ->map(fn ($id) => $mapper->rootCategoryIdForFamilyId((int) $id))
                ->filter()
                ->unique()
                ->take(3)
                ->values()
                ->all();
            $this->line('hero_quick_category_ids ← '.implode(',', $overrides['hero_quick_category_ids']));
        }

        $links = $data['hero_sidebar_links'] ?? [];
        foreach ($links as $i => $link) {
            if (! is_array($link)) {
                continue;
            }
            $url = (string) ($link['url'] ?? '');
            if (preg_match('/product_family_id=(\d+)/', $url, $matches) !== 1) {
                continue;
            }
            $categoryId = $mapper->rootCategoryIdForFamilyId((int) $matches[1]);
            if ($categoryId === null) {
                continue;
            }
            $category = Category::query()->whereKey($categoryId)->first();
            $links[$i]['url'] = $category !== null
                ? route('categories.show', $category)
                : route('products.index', ['category_id' => $categoryId]);
        }

        if ($links !== ($data['hero_sidebar_links'] ?? [])) {
            $overrides['hero_sidebar_links'] = $links;
            $this->line('hero_sidebar_links: '.count($links).' لینک به‌روز شد.');
        }

        if ($overrides === []) {
            $this->info('تغییری لازم نبود.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('[dry-run] '.json_encode($overrides, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $homepage->mergeAndPersist($overrides);
        $cache->forgetHome();
        $this->info('تنظیمات صفحه اصلی به دسته‌ها منتقل شد.');

        return self::SUCCESS;
    }
}
