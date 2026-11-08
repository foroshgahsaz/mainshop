<?php

namespace App\Services\Shop;

use App\Models\Category;

final class CategoryPathResolver
{
    public function pathFor(Category $category): string
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
