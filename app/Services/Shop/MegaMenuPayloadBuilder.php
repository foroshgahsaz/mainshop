<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\MegaMenuLink;
use App\Models\ProductFamily;
use App\Support\ShopStaffPreview;
use Illuminate\Support\Collection;

/**
 * ساختار سبک مگا منو برای کش header — بدون Eloquent در خروجی.
 */
final class MegaMenuPayloadBuilder
{
    /**
     * @return array{
     *     has_families: bool,
     *     families: list<array{
     *         id: int,
     *         name: string,
     *         all_products_url: string,
     *         columns: list<array{
     *             title: string,
     *             url: string,
     *             items: list<array{label: string, url: string}>
     *         }>
     *     }>
     * }
     */
    public function build(?bool $asStaff = null): array
    {
        $asStaff ??= ShopStaffPreview::enabled();

        $families = ProductFamily::query()
            ->where('show_in_mega', true)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'name', 'slug']);

        if ($families->isEmpty()) {
            return ['has_families' => false, 'families' => []];
        }

        $familyIds = $families->pluck('id')->all();

        $roots = Category::query()
            ->visibleOnShop($asStaff)
            ->whereIn('product_family_id', $familyIds)
            ->whereNull('parent_id')
            ->where('show_in_mega', true)
            ->orderBy('position')
            ->orderBy('id')
            ->with([
                'children' => fn ($q) => $q->visibleOnShop($asStaff)
                    ->where('show_in_mega', true)
                    ->orderBy('position')
                    ->orderBy('id'),
            ])
            ->get();

        $rootsByFamily = $roots->groupBy('product_family_id');

        $links = MegaMenuLink::query()
            ->visibleOnShop($asStaff)
            ->whereIn('product_family_id', $familyIds)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $linksByFamily = $links->groupBy('product_family_id');

        $payload = [];

        foreach ($families as $family) {
            $familyRoots = $rootsByFamily->get($family->id, collect());
            $familyLinks = $linksByFamily->get($family->id, collect());

            $columns = $this->categoryColumns($familyRoots, $familyLinks);
            $columns = array_merge($columns, $this->customColumns($familyLinks));

            $payload[] = [
                'id' => $family->id,
                'name' => $family->name,
                'all_products_url' => route('products.index', ['product_family_id' => $family->id]),
                'columns' => $columns,
            ];
        }

        return [
            'has_families' => $payload !== [],
            'families' => $payload,
        ];
    }

    /**
     * @param  Collection<int, Category>  $roots
     * @param  Collection<int, MegaMenuLink>  $familyLinks
     * @return list<array{title: string, url: string, items: list<array{label: string, url: string}>}>
     */
    private function categoryColumns(Collection $roots, Collection $familyLinks): array
    {
        $extras = $familyLinks
            ->whereNotNull('category_id')
            ->whereNull('parent_id')
            ->groupBy('category_id');

        $columns = [];

        foreach ($roots as $root) {
            $items = [];

            foreach ($root->children as $child) {
                $items[] = [
                    'label' => $child->name,
                    'url' => route('categories.show', $child),
                ];
            }

            foreach ($extras->get($root->id, collect()) as $link) {
                $items[] = [
                    'label' => $link->label,
                    'url' => $link->url,
                ];
            }

            $columns[] = [
                'title' => $root->name,
                'url' => route('categories.show', $root),
                'items' => $items,
            ];
        }

        return $columns;
    }

    /**
     * @param  Collection<int, MegaMenuLink>  $familyLinks
     * @return list<array{title: string, url: string, items: list<array{label: string, url: string}>}>
     */
    private function customColumns(Collection $familyLinks): array
    {
        $roots = $familyLinks
            ->whereNull('category_id')
            ->whereNull('parent_id');

        $childrenByParent = $familyLinks
            ->whereNotNull('parent_id')
            ->groupBy('parent_id');

        $columns = [];

        foreach ($roots as $root) {
            $items = [];

            foreach ($childrenByParent->get($root->id, collect()) as $child) {
                $items[] = [
                    'label' => $child->label,
                    'url' => $child->url,
                ];
            }

            $columns[] = [
                'title' => $root->label,
                'url' => $root->url,
                'items' => $items,
            ];
        }

        return $columns;
    }
}
