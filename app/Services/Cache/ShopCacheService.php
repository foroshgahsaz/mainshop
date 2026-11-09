<?php

namespace App\Services\Cache;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\MenuItem;
use App\Models\Post;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Shop\HomepageCreditPlanService;
use App\Services\Shop\HomepagePartnerService;
use App\Services\Shop\CatalogFamilyCategoryMapper;
use App\Services\Shop\CategoryTreeService;
use App\Services\Settings\SearchPopupSettingsService;
use App\Services\Shop\CategoryPathResolver;
use App\Services\Shop\MegaMenuPayloadBuilder;
use App\Support\ShopStaffPreview;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ShopCacheService
{
    private const LISTING_INDEX_KEY = 'shop:products:list:index';

    /** Bump when home payload shape changes so deploys never serve stale layout. */
    public const HOME_PAYLOAD_KEY = 'shop:home:payload:v4';

    public const PLANS_INDEX_KEY = 'shop:plans:index:v1';

    /** @var array<string, mixed>|null */
    private ?array $headerPayloadMemo = null;

    /** @var array<string, mixed>|null */
    private ?array $headerViewDataMemo = null;

    /**
     * دادهٔ یک‌بار-per-request برای header، overlays و mobile-menu (بدون ۳× خواندن کش).
     *
     * @return array<string, mixed>
     */
    public function headerViewData(): array
    {
        if ($this->headerViewDataMemo !== null) {
            return $this->headerViewDataMemo;
        }

        return $this->headerViewDataMemo = array_merge(
            $this->headerPayload(),
            ['searchPopup' => app(SearchPopupSettingsService::class)->all()],
        );
    }

    public function homePayload(): array
    {
        $staff = ShopStaffPreview::enabled();

        return $this->remember(
            self::HOME_PAYLOAD_KEY.$this->staffCacheSuffix($staff),
            config('shop.cache.home_ttl'),
            function () use ($staff) {
                $homepage = app(HomepageSettingsService::class);
                $productQuery = fn () => Product::query()
                    ->visibleOnShop($staff)
                    ->with(['images' => fn ($q) => $q->orderBy('position')]);

                return [
                    'sliders' => HomeSlider::active()->orderBy('position')->get(),
                    'homepage_plan_widget' => app(HomepageCreditPlanService::class)->homepageWidget(),
                    'homepage_partners' => app(HomepagePartnerService::class)->homepageItems(),
                    'discounted' => $productQuery()->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')->latest()->take(8)->get(),
                    'best_sellers' => $productQuery()->orderByDesc('views')->take(8)->get(),
                    'new' => $homepage->resolveNewProducts($productQuery),
                    'posts' => Post::query()
                        ->visibleOnShop($staff)
                        ->where(function ($q) {
                            $q->whereNull('published_at')
                                ->orWhere('published_at', '<=', now());
                        })
                        ->with('author')
                        ->latest('published_at')
                        ->take(4)
                        ->get(),
                ];
            },
            ['shop', 'home']
        );
    }

    public function categories()
    {
        $staff = ShopStaffPreview::enabled();

        return $this->remember(
            'shop:categories:active'.$this->staffCacheSuffix($staff),
            config('shop.cache.categories_ttl'),
            fn () => Category::query()->visibleOnShop($staff)->orderBy('position')->get(),
            ['shop', 'categories']
        );
    }

    public function navCategories()
    {
        $staff = ShopStaffPreview::enabled();

        return $this->remember(
            'shop:categories:nav'.$this->staffCacheSuffix($staff),
            config('shop.cache.categories_ttl'),
            fn () => Category::query()
                ->visibleOnShop($staff)
                ->where('show_in_mega', true)
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->visibleOnShop($staff)->orderBy('position')])
                ->orderBy('position')
                ->get(),
            ['shop', 'categories']
        );
    }

    public function navigation(): array
    {
        $staff = ShopStaffPreview::enabled();

        return $this->remember(
            'shop:navigation'.$this->staffCacheSuffix($staff),
            config('shop.cache.categories_ttl'),
            function () use ($staff) {
                $items = MenuItem::query()
                    ->visibleOnShop($staff)
                    ->whereNull('parent_id')
                    ->orderBy('position')
                    ->get();

                return [
                    'desktop' => $items->filter(fn ($item) => $item->showsOnDesktop())->values(),
                    'mobile' => $items->filter(fn ($item) => $item->showsOnMobile())->values(),
                    'megaPromo' => $items->firstWhere('item_type', MenuItem::TYPE_MEGA_PROMO),
                ];
            },
            ['shop', 'menus']
        );
    }

    /** @return array{searchCategories: Collection, navCategories: Collection, navigation: array} */
    public function headerPayload(): array
    {
        if ($this->headerPayloadMemo !== null) {
            return $this->headerPayloadMemo;
        }

        $staff = ShopStaffPreview::enabled();

        return $this->headerPayloadMemo = $this->remember(
            'shop:header:payload'.$this->staffCacheSuffix($staff),
            config('shop.cache.categories_ttl'),
            function () use ($staff) {
                $items = MenuItem::query()
                    ->visibleOnShop($staff)
                    ->whereNull('parent_id')
                    ->orderBy('position')
                    ->get();

                $megaMenu = app(MegaMenuPayloadBuilder::class)->build($staff);

                $navCategories = (! empty($megaMenu['has_category_tree']) || ! empty($megaMenu['has_families']))
                    ? collect()
                    : Category::query()
                        ->visibleOnShop($staff)
                        ->where('show_in_mega', true)
                        ->whereNull('parent_id')
                        ->with(['children' => fn ($q) => $q->visibleOnShop($staff)->orderBy('position')])
                        ->orderBy('position')
                        ->get();

                $searchCategories = Category::query()
                    ->visibleOnShop($staff)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->get(['id', 'name', 'slug', 'parent_id', 'position', 'is_active', 'admin_shop_preview']);

                app(CategoryPathResolver::class)->attachListingPaths($searchCategories);

                return [
                    'searchCategories' => $searchCategories,
                    'navBrands' => Brand::query()
                        ->visibleOnShop($staff)
                        ->orderBy('position')
                        ->orderBy('name')
                        ->get(),
                    'navCategories' => $navCategories,
                    'megaMenu' => $megaMenu,
                    'navigation' => [
                        'desktop' => $items->filter(fn ($item) => $item->showsOnDesktop())->values(),
                        'mobile' => $items->filter(fn ($item) => $item->showsOnMobile())->values(),
                        'megaPromo' => $items->firstWhere('item_type', MenuItem::TYPE_MEGA_PROMO),
                    ],
                ];
            },
            ['shop', 'categories', 'menus']
        );
    }

    public function brands()
    {
        $staff = ShopStaffPreview::enabled();

        return $this->remember(
            'shop:brands:active'.$this->staffCacheSuffix($staff),
            config('shop.cache.categories_ttl'),
            fn () => Brand::query()->visibleOnShop($staff)->orderBy('position')->get(),
            ['shop', 'brands']
        );
    }

    public function blogPosts(int $perPage = 12): LengthAwarePaginator
    {
        $page = (int) request()->get('page', 1);
        $staff = ShopStaffPreview::enabled();

        return $this->remember(
            'shop:blog:posts:page:'.$page.$this->staffCacheSuffix($staff),
            config('shop.cache.products_ttl'),
            fn () => Post::query()
                ->visibleOnShop($staff)
                ->when(! $staff, fn ($q) => $q->published())
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

    public function productBySlug(string $slug, ?bool $asStaff = null): ?Product
    {
        $asStaff ??= ShopStaffPreview::enabled();

        return $this->remember(
            "shop:product:{$slug}".$this->staffCacheSuffix($asStaff),
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
                ->visibleOnShop($asStaff)
                ->first(),
            ['shop', 'products']
        );
    }

    public function relatedProducts(?int $categoryId, int $excludeProductId, int $limit = 8)
    {
        if ($categoryId === null) {
            return collect();
        }

        $staff = ShopStaffPreview::enabled();

        return $this->remember(
            "shop:related:{$categoryId}:{$excludeProductId}:{$limit}".$this->staffCacheSuffix($staff),
            config('shop.cache.products_ttl'),
            fn () => Product::query()
                ->visibleOnShop($staff)
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
        $filters['_staff_preview'] = ShopStaffPreview::enabled() ? 1 : 0;
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
        $this->headerPayload();
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
        $this->headerPayloadMemo = null;
        $this->headerViewDataMemo = null;
        $this->forget('shop:categories:active');
        $this->forget('shop:categories:active:staff');
        $this->forget('shop:categories:nav');
        $this->forget('shop:categories:nav:staff');
        $this->forget('shop:header:payload');
        $this->forget('shop:header:payload:staff');
        $this->flushTag('home');
        $this->flushTag('products');
    }

    public function forgetMenus(): void
    {
        $this->headerPayloadMemo = null;
        $this->headerViewDataMemo = null;
        $this->forget('shop:navigation');
        $this->forget('shop:navigation:staff');
        $this->forget('shop:header:payload');
        $this->forget('shop:header:payload:staff');
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
        $this->forgetHomeCache();
        $this->forgetPlansIndex();
        $this->flushTag('home');
    }

    /**
     * @return list<array{id: int, label: string, intro: string, plan_url: string, icon: ?string}>
     */
    public function plansIndexCards(): array
    {
        return $this->remember(
            self::PLANS_INDEX_KEY,
            config('shop.cache.home_ttl'),
            fn () => app(HomepageCreditPlanService::class)->allActiveCards(),
            ['shop', 'home', 'plans']
        );
    }

    public function forgetPlansIndex(): void
    {
        $this->forget(self::PLANS_INDEX_KEY);
    }

    /**
     * @return array{total: int, items: Collection<int, Product>}
     */
    public function searchSuggestions(string $query, int $limit = 5): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 3) {
            return ['total' => 0, 'items' => collect()];
        }

        $builder = $this->buildProductListingQuery([
            'search' => $query,
            'sort' => 'created_at',
            'direction' => 'desc',
        ]);

        $total = (clone $builder)->count();
        $items = $builder->limit($limit)->get();

        return [
            'total' => $total,
            'items' => $items,
        ];
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
            ->visibleOnShop(ShopStaffPreview::enabled())
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
            ->when($filters['category_id'] ?? null, function ($q, $id): void {
                $categoryId = (int) $id;
                if ($categoryId <= 0) {
                    return;
                }

                if (config('shop.catalog.listing_includes_descendants', true)) {
                    $ids = app(CategoryTreeService::class)->descendantIdsIncludingSelf($categoryId);
                    $q->whereIn('category_id', $ids);
                } else {
                    $q->where('category_id', $categoryId);
                }
            })
            ->when($filters['brand_id'] ?? null, fn ($q, $id) => $q->where('brand_id', $id))
            ->when($filters['product_family_id'] ?? null, function ($q, $id): void {
                $familyId = (int) $id;
                $categoryId = app(CatalogFamilyCategoryMapper::class)->rootCategoryIdForFamilyId($familyId);
                if ($categoryId !== null && config('shop.catalog.listing_includes_descendants', true)) {
                    $ids = app(CategoryTreeService::class)->descendantIdsIncludingSelf($categoryId);
                    $q->whereIn('category_id', $ids);

                    return;
                }

                $q->where('product_family_id', $familyId);
            })
            ->when($filters['product_template_id'] ?? null, fn ($q, $id) => $q->where('product_template_id', $id))
            ->when($filters['min_price'] ?? null, fn ($q, $min) => $q->whereRaw("{$effectivePrice} >= ?", [$min]))
            ->when($filters['max_price'] ?? null, fn ($q, $max) => $q->whereRaw("{$effectivePrice} <= ?", [$max]))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');
    }

    protected function staffCacheSuffix(bool $staff): string
    {
        return $staff ? ':staff' : '';
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
        Cache::forget(self::HOME_PAYLOAD_KEY);
        Cache::forget(self::HOME_PAYLOAD_KEY.$this->staffCacheSuffix(true));
        Cache::forget('shop:home:payload');
        Cache::forget('shop:home:sliders');
        Cache::forget('shop:home:sections');
    }

    protected function supportsTags(): bool
    {
        return method_exists(Cache::getStore(), 'tags');
    }
}
