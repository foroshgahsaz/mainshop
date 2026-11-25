<?php

namespace App\Services\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Support\Collection;

final class ProductSkuLookupService
{
    public function normalizeSku(string $sku): string
    {
        $trimmed = trim($sku);

        return preg_replace('/\s+/u', '', $trimmed) ?? $trimmed;
    }

    /**
     * @return list<array{
     *     match_type: string,
     *     entity: string,
     *     entity_id: int,
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     brand: string,
     *     category: string,
     *     is_active: bool,
     *     deleted: bool,
     *     deleted_at: ?string,
     *     bankdar_label: string,
     *     in_actor_catalog: bool|null
     * }>
     */
    public function find(string $sku, ?User $actor = null, bool $allowPartial = true): array
    {
        $original = trim($sku);
        $normalized = $this->normalizeSku($sku);

        if ($normalized === '') {
            return [];
        }

        $hits = collect();

        $productMatches = $this->queryProductsBySku($original, $normalized, exact: true);
        foreach ($productMatches as $product) {
            $hits->push($this->mapProductHit($product, 'exact', $actor));
        }

        $variantMatches = $this->queryVariantsBySku($original, $normalized, exact: true);
        foreach ($variantMatches as $variant) {
            $hits->push($this->mapVariantHit($variant, 'exact', $actor));
        }

        if ($allowPartial && $hits->isEmpty() && mb_strlen($normalized) >= 4) {
            foreach ($this->queryProductsBySku($original, $normalized, exact: false) as $product) {
                $hits->push($this->mapProductHit($product, 'partial', $actor));
            }

            foreach ($this->queryVariantsBySku($original, $normalized, exact: false) as $variant) {
                $hits->push($this->mapVariantHit($variant, 'partial', $actor));
            }
        }

        return $hits
            ->unique(fn (array $row): string => $row['entity'].'-'.$row['entity_id'])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Product>
     */
    private function queryProductsBySku(string $original, string $normalized, bool $exact): Collection
    {
        return Product::query()
            ->withTrashed()
            ->with([
                'brand:id,name,bankdar_id',
                'category:id,name,bankdar_id',
            ])
            ->when($exact, function ($query) use ($original, $normalized): void {
                $query->where(function ($inner) use ($original, $normalized): void {
                    $inner
                        ->where('sku', $normalized)
                        ->orWhere('sku', $original)
                        ->orWhereRaw('REPLACE(sku, " ", "") = ?', [$normalized]);
                });
            }, function ($query) use ($normalized): void {
                $like = '%'.$normalized.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner
                        ->where('sku', 'like', $like)
                        ->orWhere('name', 'like', $like);
                });
            })
            ->orderByDesc('id')
            ->limit($exact ? 50 : 25)
            ->get();
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    private function queryVariantsBySku(string $original, string $normalized, bool $exact): Collection
    {
        return ProductVariant::query()
            ->with([
                'product' => fn ($q) => $q->withTrashed()->with(['brand:id,name,bankdar_id', 'category:id,name,bankdar_id']),
            ])
            ->when($exact, function ($query) use ($original, $normalized): void {
                $query->where(function ($inner) use ($original, $normalized): void {
                    $inner
                        ->where('sku', $normalized)
                        ->orWhere('sku', $original)
                        ->orWhereRaw('REPLACE(sku, " ", "") = ?', [$normalized]);
                });
            }, function ($query) use ($normalized): void {
                $query->where('sku', 'like', '%'.$normalized.'%');
            })
            ->orderByDesc('id')
            ->limit($exact ? 50 : 25)
            ->get();
    }

    /**
     * @return array{
     *     match_type: string,
     *     entity: string,
     *     entity_id: int,
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     brand: string,
     *     category: string,
     *     is_active: bool,
     *     deleted: bool,
     *     deleted_at: ?string,
     *     bankdar_label: string,
     *     in_actor_catalog: bool|null
     * }
     */
    private function mapProductHit(Product $product, string $matchType, ?User $actor): array
    {
        return $this->buildHitRow(
            matchType: $matchType,
            entity: 'product',
            entityId: (int) $product->id,
            product: $product,
            sku: (string) ($product->sku ?? ''),
            variantName: null,
            actor: $actor,
        );
    }

    /**
     * @return array{
     *     match_type: string,
     *     entity: string,
     *     entity_id: int,
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     brand: string,
     *     category: string,
     *     is_active: bool,
     *     deleted: bool,
     *     deleted_at: ?string,
     *     bankdar_label: string,
     *     in_actor_catalog: bool|null
     * }
     */
    private function mapVariantHit(ProductVariant $variant, string $matchType, ?User $actor): array
    {
        $product = $variant->product ?? new Product(['name' => '—']);

        return $this->buildHitRow(
            matchType: $matchType,
            entity: 'variant',
            entityId: (int) $variant->id,
            product: $product,
            sku: (string) ($variant->sku ?? ''),
            variantName: (string) $variant->name,
            actor: $actor,
        );
    }

    /**
     * @return array{
     *     match_type: string,
     *     entity: string,
     *     entity_id: int,
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     brand: string,
     *     category: string,
     *     is_active: bool,
     *     deleted: bool,
     *     deleted_at: ?string,
     *     bankdar_label: string,
     *     in_actor_catalog: bool|null
     * }
     */
    private function buildHitRow(
        string $matchType,
        string $entity,
        int $entityId,
        Product $product,
        string $sku,
        ?string $variantName,
        ?User $actor,
    ): array {
        $bankdarId = $product->brand?->bankdar_id ?? $product->category?->bankdar_id;
        $bankdarLabel = '—';
        if ($bankdarId) {
            $bankdarLabel = User::query()->whereKey($bankdarId)->value('name') ?? ('بنکدار #'.$bankdarId);
        }

        $inCatalog = null;
        if ($actor !== null && AdminAccess::isBankdarOnly($actor)) {
            $inCatalog = app(\App\Services\Catalog\BankdarCatalogService::class)
                ->productBelongsToBankdar($product, (int) $actor->id);
        }

        $productName = (string) $product->name;
        if ($variantName) {
            $productName .= ' — '.$variantName;
        }

        return [
            'match_type' => $matchType,
            'entity' => $entity,
            'entity_id' => $entityId,
            'product_id' => (int) $product->id,
            'product_name' => $productName,
            'sku' => $sku,
            'brand' => (string) ($product->brand?->name ?? '—'),
            'category' => (string) ($product->category?->name ?? '—'),
            'is_active' => (bool) $product->is_active,
            'deleted' => $product->trashed(),
            'deleted_at' => $product->deleted_at?->shopJalali(),
            'bankdar_label' => $bankdarLabel,
            'in_actor_catalog' => $inCatalog,
        ];
    }
}