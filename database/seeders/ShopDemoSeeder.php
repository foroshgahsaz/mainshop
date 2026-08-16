<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HomeSlider;
use App\Models\Post;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShopDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('در حال ساخت داده‌های نمایشی فروشگاه...');

        $categories = [
            ['name' => 'پوشاک', 'slug' => 'clothing', 'position' => 1],
            ['name' => 'کفش', 'slug' => 'shoes', 'position' => 2],
            ['name' => 'کیف', 'slug' => 'bags', 'position' => 3],
            ['name' => 'اکسسوری', 'slug' => 'accessories', 'position' => 4],
            ['name' => 'دیجیتال', 'slug' => 'electronics', 'position' => 5],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['is_active' => true])
            );
        }

        $categoryIds = Category::pluck('id', 'slug');

        $brands = [
            ['name' => 'چاپینو', 'slug' => 'chapino', 'position' => 1],
            ['name' => 'اسنوا', 'slug' => 'snowa', 'position' => 2],
            ['name' => 'پارس', 'slug' => 'pars', 'position' => 3],
            ['name' => 'آذر', 'slug' => 'azar', 'position' => 4],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => $brand['slug']],
                array_merge($brand, ['is_active' => true])
            );
        }

        $brandIds = Brand::pluck('id', 'slug');

        $author = User::updateOrCreate(
            ['phone' => '09120000001'],
            [
                'name' => 'تحریریه چاپینو',
                'slug' => 'chapino-editorial',
                'email' => 'editor@chapino.local',
                'bio' => 'تیم تحریریه مجله چاپینو',
                'is_author' => true,
                'is_admin' => false,
                'status' => true,
                'password' => bcrypt('author-demo'),
            ]
        );

        $heroImages = ['slide-ai.svg', 'slide-discount.svg', 'slide-python.svg'];
        foreach ($heroImages as $i => $hero) {
            HomeSlider::updateOrCreate(
                ['title' => 'اسلاید '.($i + 1)],
                [
                    'image' => 'shop/images/hero/'.$hero,
                    'link' => '/products',
                    'position' => $i + 1,
                    'is_active' => true,
                    'archived_at' => null,
                ]
            );
        }

        for ($i = 1; $i <= 32; $i++) {
            $slug = "demo-product-{$i}";
            $price = random_int(120000, 890000);
            $hasDiscount = $i <= 12;
            $isFeatured = $i <= 8;

            Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => "محصول نمونه {$i}",
                    'category_id' => $categoryIds->values()->random(),
                    'brand_id' => $brandIds->values()->random(),
                    'price' => $price,
                    'sale_price' => $hasDiscount ? (int) ($price * 0.75) : null,
                    'stock' => random_int(5, 80),
                    'sku' => 'DEMO-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'short_description' => 'توضیح کوتاه محصول نمونه برای نمایش در فروشگاه',
                    'is_active' => true,
                    'is_featured' => $isFeatured,
                    'views' => random_int(10, 5000),
                ]
            );
        }

        $posts = [
            ['title' => 'راهنمای خرید پوشاک زمستانه', 'slug' => 'winter-clothing-guide'],
            ['title' => 'ترندهای مد ۱۴۰۴', 'slug' => 'fashion-trends-1404'],
            ['title' => 'چطور سایز مناسب انتخاب کنیم؟', 'slug' => 'size-guide'],
            ['title' => 'مراقبت از کفش چرمی', 'slug' => 'leather-shoe-care'],
        ];

        foreach ($posts as $i => $post) {
            Post::updateOrCreate(
                ['slug' => $post['slug']],
                [
                    'title' => $post['title'],
                    'user_id' => $author->id,
                    'excerpt' => 'خلاصه مقاله نمونه برای نمایش در صفحه اصلی.',
                    'content' => 'متن کامل مقاله نمونه.',
                    'is_active' => true,
                    'published_at' => now()->subDays($i + 1),
                ]
            );
        }

        ShippingMethod::updateOrCreate(
            ['name' => 'پست پیشتاز'],
            [
                'price' => 45000,
                'estimated_days' => 3,
                'description' => '۲-۴ روز کاری',
                'is_active' => true,
            ]
        );

        DB::table('settings')->updateOrInsert(
            ['group' => 'zarinpal', 'key' => 'enabled'],
            ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
        );
        DB::table('settings')->updateOrInsert(
            ['group' => 'tara', 'key' => 'enabled'],
            ['value' => '0', 'created_at' => now(), 'updated_at' => now()]
        );

        $this->command?->info('✅ ShopDemoSeeder با موفقیت اجرا شد.');
    }
}
