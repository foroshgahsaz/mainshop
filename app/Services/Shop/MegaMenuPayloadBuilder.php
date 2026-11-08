<?php

namespace App\Services\Shop;

use App\Models\Category;
use App\Models\MegaMenuLink;
use App\Support\ShopFormatter;
use App\Support\ShopStaffPreview;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * مگا منو فقط از درخت دسته — ریل = دسته‌های ریشه show_in_mega.
 */
final class MegaMenuPayloadBuilder
{
    /**
     * @return array{
     *     has_category_tree: bool,
     *     has_families: bool,
     *     roots: list<array{
     *         id: int,
     *         name: string,
     *         all_products_url: string,
     *         columns: list<array{
     *             title: string,
     *             url: string,
     *             items: list<array{label: string, url: string}>
     *         }>
     *     }>,
     *     families: list<array>
     * }
     */
    public function build(?bool $asStaff = null): array
    {
        $asStaff ??= ShopStaffPreview::enabled();

        if (! Schema::hasTable('mega_menu_links')) {
            return $this->emptyPayload();
        }

        $roots = Category::query()
            ->visibleOnShop($asStaff)
            ->whereNull('parent_id')
            ->where('show_in_mega', true)
            ->orderBy('position')
            ->orderBy('id')
            ->with([
                'children' => fn ($q) => $q->visibleOnShop($asStaff)
                    ->where('show_in_mega', true)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->with([
                        'children' => fn ($q2) => $q2->visibleOnShop($asStaff)
                            ->where('show_in_mega', true)
                            ->orderBy('position')
                            ->orderBy('id'),
                    ]),
            ])
            ->get();

        if ($roots->isEmpty()) {
            return $this->emptyPayload();
        }

        $rootIds = $roots->pluck('id')->all();

        $links = MegaMenuLink::query()
            ->visibleOnShop($asStaff)
            ->where('is_active', true)
            ->when(
                Schema::hasColumn('mega_menu_links', 'root_category_id'),
                fn ($q) => $q->where(function ($inner) use ($rootIds): void {
                    $inner->whereIn('root_category_id', $rootIds)
                        ->orWhereIn('category_id', $rootIds);
                }),
                fn ($q) => $q->whereIn('category_id', $rootIds)
            )
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $linksByCategoryId = $links->whereNotNull('category_id')->groupBy('category_id');

        $payload = [];

        foreach ($roots as $root) {
            $columns = [];

            foreach ($root->children as $child) {
                $items = [];

                foreach ($child->children as $grandchild) {
                    $items[] = [
                        'label' => $grandchild->name,
                        'url' => route('categories.show', $grandchild),
                        'image' => $this->categoryImageUrl($grandchild->image),
                    ];
                }

                foreach ($linksByCategoryId->get($child->id, collect()) as $link) {
                    $items[] = [
                        'label' => $link->label,
                        'url' => $link->url,
                        'image' => null,
                    ];
                }

                $columns[] = [
                    'title' => $child->name,
                    'url' => route('categories.show', $child),
                    'image' => $this->categoryImageUrl($child->image),
                    'items' => $items,
                ];
            }

            $columns = array_merge($columns, $this->customColumnsForRoot($links, $root->id));

            $payload[] = [
                'id' => $root->id,
                'name' => $root->name,
                'image' => $this->categoryImageUrl($root->image),
                'all_products_url' => route('categories.show', $root),
                'columns' => $columns,
            ];
        }

        return [
            'has_category_tree' => true,
            'has_families' => true,
            'roots' => $payload,
            'families' => $payload,
        ];
    }

    /**
     * @param  Collection<int, MegaMenuLink>  $links
     * @return list<array{title: string, url: string, items: list<array{label: string, url: string}>}>
     */
    private function customColumnsForRoot(Collection $links, int $rootId): array
    {
        $scoped = $links->filter(function (MegaMenuLink $link) use ($rootId): bool {
            if ($link->category_id !== null) {
                return false;
            }

            if (Schema::hasColumn('mega_menu_links', 'root_category_id')) {
                return (int) $link->root_category_id === $rootId;
            }

            return $link->parent_id === null && (int) ($link->product_family_id ?? 0) === 0;
        });

        $roots = $scoped->whereNull('parent_id');
        $childrenByParent = $scoped->whereNotNull('parent_id')->groupBy('parent_id');

        $columns = [];

        foreach ($roots as $root) {
            $items = [];

            foreach ($childrenByParent->get($root->id, collect()) as $child) {
                $items[] = [
                    'label' => $child->label,
                    'url' => $child->url,
                    'image' => null,
                ];
            }

            $columns[] = [
                'title' => $root->label,
                'url' => $root->url,
                'image' => null,
                'items' => $items,
            ];
        }

        return $columns;
    }

    private function categoryImageUrl(mixed $path): string
    {
        $trimmed = trim((string) $path);

        return ShopFormatter::categoryImage($trimmed !== '' ? $trimmed : null);
    }

    /** @return array{has_category_tree: false, has_families: false, roots: array, families: array} */
    private function emptyPayload(): array
    {
        return [
            'has_category_tree' => false,
            'has_families' => false,
            'roots' => [],
            'families' => [],
        ];
    }
}
