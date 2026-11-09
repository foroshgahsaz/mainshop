<?php

namespace App\Services\Shop;

use App\Models\Category;
use Illuminate\Support\Collection;

final class CategoryPathResolver
{
    public function pathFor(Category $category): string
    {
        return $this->pathForUncached($category);
    }

    /**
     * @return list<Category> از ریشه تا برگ
     */
    public function ancestryChain(Category $category): array
    {
        $chain = [];
        $node = $category;

        while ($node !== null) {
            array_unshift($chain, $node);
            if (! $node->parent_id) {
                break;
            }
            $node = Category::query()->whereKey($node->parent_id)->first();
        }

        return $chain;
    }

    /**
     * @return list<array{id: int, name: string, slug: string, path: string}>
     */
    public function breadcrumbTrail(Category $category): array
    {
        $trail = [];
        $slugs = [];

        foreach ($this->ancestryChain($category) as $node) {
            $slugs[] = (string) $node->slug;
            $trail[] = [
                'id' => (int) $node->id,
                'name' => (string) $node->name,
                'slug' => (string) $node->slug,
                'path' => implode('/', $slugs),
            ];
        }

        return $trail;
    }

    /**
     * مسیر همهٔ دسته‌ها با یک بار خواندن flat — برای کش هدر و جستجو.
     *
     * @param  Collection<int, Category>  $categories
     */
    public function attachListingPaths(Collection $categories): Collection
    {
        $byId = $categories->keyBy('id');

        return $categories->each(function (Category $category) use ($byId): void {
            $slugs = [];
            $node = $category;

            while ($node !== null) {
                array_unshift($slugs, (string) $node->slug);
                if (! $node->parent_id) {
                    break;
                }
                $node = $byId->get((int) $node->parent_id);
                if ($node === null) {
                    break;
                }
            }

            $category->setAttribute('listing_path', implode('/', $slugs));
        });
    }

    private function pathForUncached(Category $category): string
    {
        $slugs = [];
        $node = $category;

        while ($node !== null) {
            array_unshift($slugs, (string) $node->slug);
            if (! $node->parent_id) {
                break;
            }
            $node = Category::query()->whereKey($node->parent_id)->first();
        }

        return implode('/', $slugs);
    }

    public function resolve(string $path): ?Category
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn (string $s) => $s !== ''));
        if ($segments === []) {
            return null;
        }

        if (count($segments) === 1) {
            return Category::query()->where('slug', $segments[0])->first();
        }

        $current = null;

        foreach ($segments as $index => $slug) {
            $query = Category::query()->where('slug', $slug);

            if ($index === 0) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $current?->id);
            }

            $current = $query->first();
            if ($current === null) {
                return null;
            }
        }

        return $current;
    }
}
