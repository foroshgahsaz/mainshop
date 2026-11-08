<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\ProductFamily;
use App\Services\Shop\CatalogFamilyCategoryMapper;
use Illuminate\Console\Command;

class CatalogDiagnoseFamiliesCommand extends Command
{
    protected $signature = 'shop:catalog-diagnose-families';

    protected $description = 'گزارش نگاشت خانواده محصول → دستهٔ ریشه (برای مهاجرت کاتالوگ)';

    public function handle(CatalogFamilyCategoryMapper $mapper): int
    {
        $rows = [];

        ProductFamily::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'name', 'slug'])
            ->each(function (ProductFamily $family) use ($mapper, &$rows): void {
                $rootId = $mapper->rootCategoryIdForFamily($family);
                $inferred = $mapper->inferRootFromProducts((int) $family->id);

                $rootName = $rootId !== null
                    ? (string) Category::query()->whereKey($rootId)->value('name')
                    : '—';

                $source = '—';
                if ($rootId !== null) {
                    $source = $rootId === $inferred ? 'map/infer' : 'map';
                } elseif ($inferred !== null) {
                    $source = 'infer-only';
                }

                $rows[] = [
                    (string) $family->id,
                    $family->name,
                    $family->slug,
                    $rootId !== null ? (string) $rootId : '—',
                    $rootName,
                    $inferred !== null ? (string) $inferred : '—',
                    $source,
                ];
            });

        $this->table(
            ['family_id', 'name', 'slug', 'root_id', 'root_name', 'infer_id', 'note'],
            $rows
        );

        $unmapped = collect($rows)->where('root_id', '—')->count();
        if ($unmapped > 0) {
            $this->warn("{$unmapped} خانواده بدون ریشه — در .env نگاشت دستی بگذارید: SHOP_FAMILY_ROOT_CATEGORY_MAP='{\"id\":root_id}'");
        } else {
            $this->info('همه خانواده‌های فعال به ریشهٔ دسته وصل شدند.');
        }

        return self::SUCCESS;
    }
}
