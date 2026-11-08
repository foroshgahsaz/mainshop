<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\MegaMenuLink;
use App\Models\ProductFamily;
use App\Services\Cache\ShopCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class MigrateMegaMenuToCategoryRootsCommand extends Command
{
    protected $signature = 'shop:migrate-mega-menu-roots
                            {--dry-run : فقط گزارش}
                            {--enable-roots : show_in_mega را روی دسته‌های ریشه فعال کند}';

    protected $description = 'لینک‌های مگا منو را از product_family_id به root_category_id منتقل می‌کند (بدون تغییر slug محصول)';

    public function handle(ShopCacheService $cache): int
    {
        if (! Schema::hasTable('mega_menu_links') || ! Schema::hasColumn('mega_menu_links', 'root_category_id')) {
            $this->error('ستون root_category_id وجود ندارد. ابتدا migrate را اجرا کنید.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $enableRoots = (bool) $this->option('enable-roots');

        $familyToRoot = [];
        $families = ProductFamily::query()->where('is_active', true)->get(['id', 'name']);

        foreach ($families as $family) {
            $root = Category::query()
                ->whereNull('parent_id')
                ->where('product_family_id', $family->id)
                ->orderBy('position')
                ->orderBy('id')
                ->first();

            if ($root === null) {
                $root = Category::query()
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('position')
                    ->first();
            }

            if ($root !== null) {
                $familyToRoot[$family->id] = $root->id;
                $this->line("خانواده «{$family->name}» → ریشه دسته «{$root->name}» (#{$root->id})");
            }
        }

        $linksUpdated = 0;

        MegaMenuLink::query()
            ->whereNull('root_category_id')
            ->whereNotNull('product_family_id')
            ->orderBy('id')
            ->each(function (MegaMenuLink $link) use ($familyToRoot, $dryRun, &$linksUpdated): void {
                $rootId = $familyToRoot[(int) $link->product_family_id] ?? null;
                if ($rootId === null) {
                    return;
                }

                $linksUpdated++;
                if (! $dryRun) {
                    $link->update(['root_category_id' => $rootId]);
                }
            });

        $rootsEnabled = 0;
        if ($enableRoots) {
            foreach (array_unique(array_values($familyToRoot)) as $rootId) {
                $rootsEnabled++;
                if (! $dryRun) {
                    Category::query()->whereKey($rootId)->update(['show_in_mega' => true]);
                }
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."لینک‌ها: {$linksUpdated} | ریشه‌های show_in_mega: {$rootsEnabled}");

        if (! $dryRun) {
            $cache->forgetMenus();
            $cache->forgetCategory();
        }

        return self::SUCCESS;
    }
}
