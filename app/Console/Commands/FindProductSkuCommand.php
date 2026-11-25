<?php

namespace App\Console\Commands;

use App\Services\Product\ProductSkuLookupService;
use Illuminate\Console\Command;

class FindProductSkuCommand extends Command
{
    protected $signature = 'shop:find-sku {sku : کد کالا/SKU} {--json : خروجی JSON}';

    protected $description = 'Find products/variants by SKU (includes soft-deleted)';

    public function handle(ProductSkuLookupService $lookup): int
    {
        $sku = (string) $this->argument('sku');
        $results = $lookup->find($sku, null);

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($results === []) {
            $this->warn('No matches for SKU: '.$lookup->normalizeSku($sku));

            return self::SUCCESS;
        }

        $this->table(
            ['entity', 'entity_id', 'product_id', 'sku', 'name', 'deleted', 'brand', 'category'],
            array_map(static fn (array $row): array => [
                $row['entity'],
                $row['entity_id'],
                $row['product_id'],
                $row['sku'],
                $row['product_name'],
                $row['deleted'] ? 'yes' : 'no',
                $row['brand'],
                $row['category'],
            ], $results)
        );

        return self::SUCCESS;
    }
}