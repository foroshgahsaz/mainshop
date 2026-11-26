<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;

class BackfillProductCreatedByCommand extends Command
{
    protected $signature = 'shop:backfill-product-created-by
                            {--dry-run : فقط گزارش؛ بدون ذخیره}
                            {--limit= : حداکثر تعداد به‌روزرسانی}';

    protected $description = 'Set products.created_by_user_id from last_edited_by for legacy rows (sales managers)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit');
        $max = $limit !== null && $limit !== '' ? max(1, (int) $limit) : null;

        $salesManagerIds = User::query()
            ->where('is_sales_manager', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($salesManagerIds === []) {
            $this->warn('No sales managers in database.');

            return self::SUCCESS;
        }

        $query = Product::query()
            ->withTrashed()
            ->whereNull('created_by_user_id')
            ->whereNotNull('last_edited_by_user_id')
            ->whereIn('last_edited_by_user_id', $salesManagerIds)
            ->orderBy('id');

        $total = (clone $query)->count();
        $this->info("Legacy products eligible for backfill: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->comment('Dry run — no rows updated. Omit --dry-run to apply.');

            $sample = (clone $query)->limit(15)->get(['id', 'name', 'sku', 'last_edited_by_user_id']);
            $this->table(['id', 'name', 'sku', 'last_edited_by_user_id'], $sample->map(fn (Product $p) => [
                $p->id,
                mb_substr((string) $p->name, 0, 40),
                $p->sku,
                $p->last_edited_by_user_id,
            ])->all());

            return self::SUCCESS;
        }

        $updated = 0;
        $query->chunkById(200, function ($products) use (&$updated, $max): void {
            foreach ($products as $product) {
                if ($max !== null && $updated >= $max) {
                    return false;
                }

                /** @var Product $product */
                $product->forceFill([
                    'created_by_user_id' => $product->last_edited_by_user_id,
                ])->saveQuietly();
                $updated++;
            }
        });

        $this->info("Updated created_by_user_id on {$updated} product(s).");

        return self::SUCCESS;
    }
}