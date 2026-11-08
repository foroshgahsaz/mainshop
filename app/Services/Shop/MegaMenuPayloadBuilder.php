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
                $items = $this->sortedColumnItems($child, $linksByCategoryId->get($child->id, collect()));

                $columns[] = [
                    'title' => $child->name,
                    'url' => route('categories.show', $child),
                    'image' => $this->categoryImageUrl($child->image),
                    'items' => $items,
                    'sort' => (int) $child->position,
                    'sort_id' => (int) $child->id,
                ];
            }

            $columns = $this->sortMegaColumns(array_merge($columns, $this->customColumnsForRoot($links, $root->id)));

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
            $entries = [];

            foreach ($childrenByParent->get($root->id, collect()) as $child) {
                $entries[] = [
                    'sort' => (int) $child->position,
                    'sort_id' => (int) $child->id,
                    'label' => $child->label,
                    'url' => $child->url,
                    'image' => null,
                ];
            }

            usort($entries, static function (array $a, array $b): int {
                $byPos = $a['sort'] <=> $b['sort'];
                if ($byPos !== 0) {
                    return $byPos;
                }

                return $a['sort_id'] <=> $b['sort_id'];
            });

            $items = array_map(static fn (array $row): array => [
                'label' => $row['label'],
                'url' => $row['url'],
                'image' => $row['image'],
            ], $entries);

            $columns[] = [
                'title' => $root->label,
                'url' => $root->url,
                'image' => null,
                'items' => $items,
                'sort' => (int) $root->position,
                'sort_id' => (int) $root->id,
            ];
        }

        return $columns;
    }

    /**
     * @param  Collection<int, MegaMenuLink>  $childLinks
     * @return list<array{label: string, url: string, image: ?string}>
     */
    private function sortedColumnItems(Category $child, Collection $childLinks): array
    {
        $entries = [];

        foreach ($child->children as $grandchild) {
            $entries[] = [
                'sort' => (int) $grandchild->position,
                'sort_id' => (int) $grandchild->id,
                'label' => $grandchild->name,
                'url' => route('categories.show', $grandchild),
                'image' => $this->categoryImageUrl($grandchild->image),
            ];
        }

        foreach ($childLinks as $link) {
            $entries[] = [
                'sort' => (int) $link->position,
                'sort_id' => 1_000_000 + (int) $link->id,
                'label' => $link->label,
                'url' => $link->url,
                'image' => null,
            ];
        }

        usort($entries, static function (array $a, array $b): int {
            $byPos = $a['sort'] <=> $b['sort'];
            if ($byPos !== 0) {
                return $byPos;
            }

            return $a['sort_id'] <=> $b['sort_id'];
        });

        return array_map(static fn (array $row): array => [
            'label' => $row['label'],
            'url' => $row['url'],
            'image' => $row['image'],
        ], $entries);
    }

    /**
     * @param  list<array<string, mixed>>  $columns
     * @return list<array<string, mixed>>
     */
    private function sortMegaColumns(array $columns): array
    {
        usort($columns, static function (array $a, array $b): int {
            $byPos = ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0));
            if ($byPos !== 0) {
                return $byPos;
            }

            return ((int) ($a['sort_id'] ?? 0)) <=> ((int) ($b['sort_id'] ?? 0));
        });

        return array_map(static function (array $column): array {
            unset($column['sort'], $column['sort_id']);

            return $column;
        }, $columns);
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
