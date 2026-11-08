<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ProductFamily;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Shop\CatalogFamilyCategoryMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

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

        if (($data['taxonomy_mode'] ?? HomepageSettingsService::TAXONOMY_CATEGORIES) !== HomepageSettingsService::TAXONOMY_CATEGORIES) {
            $overrides['taxonomy_mode'] = HomepageSettingsService::TAXONOMY_CATEGORIES;
            $this->line('taxonomy_mode ← categories');
        }

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

        $sidebarCategoryId = (int) ($overrides['hero_sidebar_category_id'] ?? $data['hero_sidebar_category_id'] ?? 0);

        if (($data['hero_quick_category_ids'] ?? []) === [] && ! isset($overrides['hero_quick_category_ids'])) {
            $quickIds = $this->defaultQuickCategoryIds($mapper, $sidebarCategoryId);
            if ($quickIds !== []) {
                $overrides['hero_quick_category_ids'] = $quickIds;
                $this->line('hero_quick_category_ids (پیش‌فرض) ← '.implode(',', $quickIds));
            }
        }

        if (empty($data['hero_sidebar_category_id']) && $familySidebar === null && Schema::hasTable('product_families')) {
            $chineseFamily = ProductFamily::query()
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->where('slug', 'chinese-service')
                        ->orWhere('name', 'سرویس چینی');
                })
                ->first();
            if ($chineseFamily !== null) {
                $mapped = $mapper->rootCategoryIdForFamily($chineseFamily);
                if ($mapped !== null) {
                    $overrides['hero_sidebar_category_id'] = $mapped;
                    $name = Category::query()->whereKey($mapped)->value('name');
                    if ($name) {
                        $overrides['hero_sidebar_title'] = $name;
                    }
                    $this->line("hero_sidebar_category_id (سرویس چینی) ← #{$mapped}");
                    $sidebarCategoryId = $mapped;
                }
            }
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

        $after = $homepage->all();
        if (($after['hero_sidebar_links'] ?? []) === [] && ($after['hero_sidebar_category_id'] ?? null)) {
            $payload = $after;
            $category = Category::query()->whereKey((int) $after['hero_sidebar_category_id'])->first();
            if ($category !== null) {
                $payload = array_replace_recursive($after, [
                    'hero_sidebar_links' => app(\App\Services\Shop\CategoryHomepageHeroService::class)
                        ->sidebarLinksForRootCategory($category, resolveImageUrls: false),
                ]);
                $homepage->mergeAndPersist([
                    'hero_sidebar_links' => $payload['hero_sidebar_links'],
                ]);
                $this->line('hero_sidebar_links از دستهٔ سایدبار پر شد.');
            }
        }

        $cache->forgetHome();
        $this->info('تنظیمات صفحه اصلی به دسته‌ها منتقل شد.');

        return self::SUCCESS;
    }

    /** @return list<int> */
    protected function defaultQuickCategoryIds(CatalogFamilyCategoryMapper $mapper, int $excludeRootId): array
    {
        if (! Schema::hasTable('product_families')) {
            return [];
        }

        $preferSlugs = ['crystal-service', 'kitchenware', 'furniture', 'pars-opal'];
        $ids = collect();

        ProductFamily::query()
            ->where('is_active', true)
            ->whereIn('slug', $preferSlugs)
            ->orderBy('position')
            ->each(function (ProductFamily $family) use ($mapper, $excludeRootId, $ids): void {
                $rootId = $mapper->rootCategoryIdForFamily($family);
                if ($rootId === null || $rootId === $excludeRootId) {
                    return;
                }
                $ids->push($rootId);
            });

        if ($ids->count() < 3) {
            ProductFamily::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->each(function (ProductFamily $family) use ($mapper, $excludeRootId, $ids): void {
                    if (in_array($family->slug, ['chinese-service'], true)) {
                        return;
                    }
                    $rootId = $mapper->rootCategoryIdForFamily($family);
                    if ($rootId === null || $rootId === $excludeRootId) {
                        return;
                    }
                    $ids->push($rootId);
                });
        }

        return $ids->unique()->take(3)->values()->all();
    }
}
