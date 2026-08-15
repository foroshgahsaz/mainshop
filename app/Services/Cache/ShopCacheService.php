<?php

namespace App\Services\Cache;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\Post;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShopCacheService
{
    private const LISTING_INDEX_KEY = 'shop:products:list:index';

    public function homePayload(): array
    {
        return $this->remember(
            'shop:home:payload',
            config('shop.cache.home_ttl'),
            function () {
                $productQuery = fn () => Product::active()->with(['images' => fn ($q) => $q->orderBy('position')]);

                return [
                    'sliders' => HomeSlider::active()->orderBy('position')->get(),
                    'categories' => Category::where('is_active', true)->orderBy('position')->get(),
                    'discounted' => $productQuery()->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')->latest()->take(8)->get(),
                    'best_sellers' => $productQuery()->orderByDesc('views')->take(8)->get(),
                    'new' => $productQuery()->latest()->take(8)->get(),
                    'posts' => Post::published()->with('author')->latest('published_at')->take(4)->get(),
                ];
            },
            ['shop', 'home']
        );
    }

    public function categories()
    {
        return $this->remember(
            'shop:categories:active',
            config('shop.cache.categories_ttl'),
            fn () => Category::where('is_active', true)->orderBy('position')->get(),
            ['shop', 'categories']
        );
    }

    public function navCategories()
    {
        return $this->remember(
            'shop:categories:nav',
            config('shop.cache.categories_ttl'),
            fn () => Category::query()
                ->where('is_active', true)
                ->where('show_in_mega', true)
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
                ->orderBy('position')
                ->get(),
            ['shop', 'categories']
        );
    }

    public function navigation(): array
    {
        return $this->remember(
            'shop:navigation',
            config('shop.cache.categories_ttl'),
            function () {
                $items = \App\Models\MenuItem::query()
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('position')
                    ->get();

                return [
                    'desktop' => $items->filter(fn ($item) => $item->showsOnDesktop())->values(),
                    'mobile' => $items->filter(fn ($item) => $item->showsOnMobile())->values(),
                    'megaPromo' => $items->firstWhere('item_type', \App\Models\MenuItem::TYPE_MEGA_PROMO),
                ];
            },
            ['shop', 'menus']
        );
    }

    /** @return array{searchCategories: \Illuminate\Support\Collection, navCategories: \Illuminate\Support\Collection, navigation: array} */
    public function headerPayload(): array
    {
        return $this->remember(
            'shop:header:payload',
            config('shop.cache.categories_ttl'),
            function () {
                $items = \App\Models\MenuItem::query()
                    ->where('is_active', true)
                    ->whereNull('parent_id')
                    ->orderBy('position')
                    ->get();

                return [
                    'searchCategories' => Category::where('is_active', true)->orderBy('position')->get(),
                    'navCategories' => Category::query()
                        ->where('is_active', true)
                        ->where('show_in_mega', true)
                        ->whereNull('parent_id')
                        ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('position')])
                        ->orderBy('position')
                        ->get(),
                    'navigation' => [
                        'desktop' => $items->filter(fn ($item) => $item->showsOnDesktop())->values(),
                        'mobile' => $items->filter(fn ($item) => $item->showsOnMobile())->values(),
                        'megaPromo' => $items->firstWhere('item_type', \App\Models\MenuItem::TYPE_MEGA_PROMO),
                    ],
                ];
            },
            ['shop', 'categories', 'menus']
        );
    }

    public function brands()
    {
        return $this->remember(
            'shop:brands:active',
            config('shop.cache.categories_ttl'),
            fn () => Brand::where('is_active', true)->orderBy('position')->get(),
            ['shop', 'brands']
        );
    }

    public function blogPosts(int $perPage = 12): LengthAwarePaginator
    {
        $page = (int) request()->get('page', 1);

        return $this->remember(
            'shop:blog:posts:page:'.$page,
            config('shop.cache.products_ttl'),
            fn () => Post::published()
                ->with('author')
                ->latest('published_at')
                ->paginate($perPage, ['*'], 'page', $page),
            ['shop', 'blog']
        );
    }

    /** @deprecated use homePayload() */
    public function homeSections(): array
    {
        $payload = $this->homePayload();

        return [
            'discounted' => $payload['discounted'],
            'best_sellers' => $payload['best_sellers'],
            'new' => $payload['new'],
        ];
    }

    public function productBySlug(string $slug): ?Product
    {
        return $this->remember(
            "shop:product:{$slug}",
            config('shop.cache.products_ttl'),
            fn () => Product::query()
                ->with([
                    'category',
                    'brand',
                    'images' => fn ($q) => $q->orderBy('position'),
                    'attributes' => fn ($q) => $q->wherePivot('is_variation', true)->orderByPivot('position'),
                    'attributes.values' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
                    'variants' => fn ($q) => $q->where('is_active', true)->with('attributeValues.attribute'),
                ])
                ->withCount([
                    'reviews as approved_reviews_count' => fn ($q) => $q->where('is_approved', true),
                    'questions as approved_questions_count' => fn ($q) => $q->where('is_approved', true),
                ])
                ->withAvg([
                    'reviews as approved_reviews_avg' => fn ($q) => $q->where('is_approved', true),
                ], 'rating')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first(),
            ['shop', 'products']
        );
    }

    public function relatedProducts(int $categoryId, int $excludeProductId, int $limit = 8)
    {
        return $this->remember(
            "shop:related:{$categoryId}:{$excludeProductId}:{$limit}",
            config('shop.cache.products_ttl'),
            fn () => Product::active()
                ->with(['images' => fn ($q) => $q->orderBy('position')])
                ->where('category_id', $categoryId)
                ->where('id', '!=', $excludeProductId)
                ->latest()
                ->take($limit)
                ->get(),
            ['shop', 'products']
        );
    }

    public function productListing(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        if (! empty($filters['search'])) {
            return $this->buildProductListingQuery($filters)->paginate($perPage);
        }

        $page = (int) ($filters['page'] ?? request()->get('page', 1));
        $filters['page'] = $page;
        $cacheKey = 'shop:products:list:'.md5(json_encode($filters));
        $ttl = config('shop.cache.products_ttl');

        $callback = fn () => $this->buildProductListingQuery($filters)->paginate($perPage, ['*'], 'page', $page);

        if ($this->supportsTags()) {
            return Cache::tags(['shop', 'products'])->remember($cacheKey, $ttl, $callback);
        }

        $this->trackListingKey($cacheKey);

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    public function productPriceBounds(): array
    {
        return $this->remember(
            'shop:products:price-bounds',
            config('shop.cache.products_ttl'),
            function () {
                $effectivePrice = 'COALESCE(CASE WHEN sale_price IS NOT NULL AND sale_price < price THEN sale_price ELSE price END, price)';

                $min = (int) Product::query()->active()->min(DB::raw($effectivePrice));
                $max = (int) Product::query()->active()->max(DB::raw($effectivePrice));

                $max = max($max, 1000000);
                $max = (int) (ceil($max / 100000) * 100000);

                return [
                    'min' => max(0, $min),
                    'max' => $max,
                ];
            },
            ['shop', 'products']
        );
    }

    public function shippingMethods()
    {
        return $this->remember(
            'shop:shipping:active',
            config('shop.cache.shipping_ttl'),
            fn () => ShippingMethod::where('is_active', true)->get(),
            ['shop', 'shipping']
        );
    }

    public function sliders()
    {
        return $this->remember(
            'shop:home:sliders',
            config('shop.cache.home_ttl'),
            fn () => HomeSlider::active()->orderBy('position')->get(),
            ['shop', 'home']
        );
    }

    public function warm(): void
    {
        $this->homePayload();
        $this->categories();
        $this->brands();
        $this->productPriceBounds();
        $this->shippingMethods();
        $this->productListing(['sort' => 'created_at', 'direction' => 'desc', 'page' => 1]);
    }

    public function forgetProduct(Product $product): void
    {
        $this->forget("shop:product:{$product->slug}");
        $this->flushTag('home');
        $this->flushTag('products');
    }

    public function forgetCategory(): void
    {
        $this->forget('shop:categories:active');
        $this->forget('shop:categories:nav');
        $this->forget('shop:header:payload');
        $this->flushTag('home');
        $this->flushTag('products');
    }

    public function forgetMenus(): void
    {
        $this->forget('shop:navigation');
        $this->forget('shop:header:payload');
        if ($this->supportsTags()) {
            Cache::tags(['shop', 'menus'])->flush();
        }
    }

    public function forgetBrand(): void
    {
        $this->forget('shop:brands:active');
        $this->flushTag('products');
    }

    public function forgetBlog(): void
    {
        if ($this->supportsTags()) {
            Cache::tags(['shop', 'blog'])->flush();

            return;
        }

        for ($page = 1; $page <= 50; $page++) {
            Cache::forget('shop:blog:posts:page:'.$page);
        }
    }

    public function forgetShipping(): void
    {
        $this->forget('shop:shipping:active');
    }

    public function forgetHome(): void
    {
        $this->flushTag('home');
    }

    public function forgetProductListings(): void
    {
        if ($this->supportsTags()) {
            Cache::tags(['shop', 'products'])->flush();

            return;
        }

        foreach (Cache::get(self::LISTING_INDEX_KEY, []) as $key) {
            Cache::forget($key);
        }

        Cache::forget(self::LISTING_INDEX_KEY);
        Cache::forget('shop:products:price-bounds');
    }

    protected function buildProductListingQuery(array $filters)
    {
        $effectivePrice = 'COALESCE(CASE WHEN sale_price IS NOT NULL AND sale_price < price THEN sale_price ELSE price END, price)';

        return Product::query()
            ->with(['images' => fn ($q) => $q->orderBy('position'), 'category', 'brand'])
            ->active()
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('short_description', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['category_id'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
            ->when($filters['min_price'] ?? null, fn ($q, $min) => $q->whereRaw("{$effectivePrice} >= ?", [$min]))
            ->when($filters['max_price'] ?? null, fn ($q, $max) => $q->whereRaw("{$effectivePrice} <= ?", [$max]))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');
    }

    protected function trackListingKey(string $key): void
    {
        if ($this->supportsTags()) {
            return;
        }

        $index = Cache::get(self::LISTING_INDEX_KEY, []);

        if (! in_array($key, $index, true)) {
            $index[] = $key;
            Cache::put(
                self::LISTING_INDEX_KEY,
                $index,
                config('shop.cache.products_ttl') * 24
            );
        }
    }

    protected function remember(string $key, int $ttl, callable $callback, array $tags = [])
    {
        if ($this->supportsTags()) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        }

        return Cache::remember($key, $ttl, $callback);
    }

    protected function forget(string $key): void
    {
        Cache::forget($key);
    }

    protected function flushTag(string $tag): void
    {
        if ($this->supportsTags()) {
            Cache::tags(['shop', $tag])->flush();

            return;
        }

        match ($tag) {
            'home' => $this->forgetHomeCache(),
            'products' => $this->forgetProductListings(),
            'blog' => $this->forgetBlog(),
            default => null,
        };
    }

    protected function forgetHomeCache(): void
    {
        Cache::forget('shop:home:payload');
        Cache::forget('shop:home:sliders');
        Cache::forget('shop:home:sections');
    }

    protected function supportsTags(): bool
    {
        return method_exists(Cache::getStore(), 'tags');
    }
}
