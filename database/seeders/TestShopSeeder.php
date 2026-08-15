<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestShopSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | 1) Categories (5)
            |--------------------------------------------------------------------------
            */
            $categories = [
                ['name' => 'Clothing', 'slug' => 'clothing'],
                ['name' => 'Shoes', 'slug' => 'shoes'],
                ['name' => 'Bags', 'slug' => 'bags'],
                ['name' => 'Accessories', 'slug' => 'accessories'],
                ['name' => 'Electronics', 'slug' => 'electronics'],
            ];

            foreach ($categories as &$cat) {
                $cat['is_active'] = 1;
                $cat['created_at'] = now();
                $cat['updated_at'] = now();
            }

            foreach ($categories as $cat) {
                DB::table('categories')->updateOrInsert(
                    ['slug' => $cat['slug']],
                    $cat
                );
            }

            // دریافت ID دسته Clothing
            $clothingId = DB::table('categories')->where('slug', 'clothing')->value('id');


            /*
            |--------------------------------------------------------------------------
            | 2) Attributes (Color / Size)
            |--------------------------------------------------------------------------
            */
            $colorAttr = DB::table('attributes')->where('slug', 'color')->value('id');
            if (!$colorAttr) {
                $colorAttr = DB::table('attributes')->insertGetId([
                    'name' => 'Color',
                    'slug' => 'color',
                    'type' => 'select',
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            $sizeAttr = DB::table('attributes')->where('slug', 'size')->value('id');
            if (!$sizeAttr) {
                $sizeAttr = DB::table('attributes')->insertGetId([
                    'name' => 'Size',
                    'slug' => 'size',
                    'type' => 'select',
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 3) Attribute Values
            |--------------------------------------------------------------------------
            */
            $colors = ['Red', 'Blue', 'Green'];
            $sizes = ['S', 'M', 'L'];

            $colorValueIds = [];
            foreach ($colors as $c) {
                $existing = DB::table('attribute_values')
                    ->where('attribute_id', $colorAttr)
                    ->where('value', $c)
                    ->value('id');

                if ($existing) {
                    $colorValueIds[$c] = $existing;
                } else {
                    $colorValueIds[$c] = DB::table('attribute_values')->insertGetId([
                        'attribute_id' => $colorAttr,
                        'value' => $c,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            $sizeValueIds = [];
            foreach ($sizes as $s) {
                $existing = DB::table('attribute_values')
                    ->where('attribute_id', $sizeAttr)
                    ->where('value', $s)
                    ->value('id');

                if ($existing) {
                    $sizeValueIds[$s] = $existing;
                } else {
                    $sizeValueIds[$s] = DB::table('attribute_values')->insertGetId([
                        'attribute_id' => $sizeAttr,
                        'value' => $s,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 4) 2000 Products (با جلوگیری از تکرار اسلاگ)
            |--------------------------------------------------------------------------
            */
            $productIds = [];

            $this->command->info('در حال ساخت 2000 محصول...');

            for ($i = 1; $i <= 2000; $i++) {

                // ساخت اسلاگ یکتا با استفاده از uniqueId اگر اسلاگ تکراری بود
                $baseSlug = "tshirt-model-$i";
                $slug = $baseSlug;
                $counter = 1;

                while (DB::table('products')->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $sku = "TSHIRT-" . $i . "-" . Str::random(4);

                // چک کن آیا محصول با این اسلاگ وجود دارد (دوباره چک امنیتی)
                $existingProduct = DB::table('products')->where('slug', $slug)->first();

                if ($existingProduct) {
                    $productId = $existingProduct->id;
                } else {
                    $productId = DB::table('products')->insertGetId([
                        'name' => "T-Shirt Model $i",
                        'slug' => $slug,
                        'category_id' => $clothingId,
                        'price' => rand(50000, 500000),
                        'stock' => rand(0, 100),
                        'sku' => $sku,
                        'is_active' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $productIds[] = $productId;

                // Connect attributes to product
                $existsColor = DB::table('product_attributes')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $colorAttr)
                    ->exists();

                if (!$existsColor) {
                    DB::table('product_attributes')->insert([
                        'product_id' => $productId,
                        'attribute_id' => $colorAttr
                    ]);
                }

                $existsSize = DB::table('product_attributes')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $sizeAttr)
                    ->exists();

                if (!$existsSize) {
                    DB::table('product_attributes')->insert([
                        'product_id' => $productId,
                        'attribute_id' => $sizeAttr
                    ]);
                }

                // Create Variants
                foreach ($colors as $c) {
                    foreach ($sizes as $s) {

                        $variantSku = "TSHIRT-$i-$c-$s-" . Str::random(3);
                        $existingVariant = DB::table('product_variants')
                            ->where('product_id', $productId)
                            ->where('name', "T-Shirt $c $s")
                            ->first();

                        if (!$existingVariant) {
                            $variantId = DB::table('product_variants')->insertGetId([
                                'product_id' => $productId,
                                'name' => "T-Shirt $c $s",
                                'sku' => $variantSku,
                                'price' => rand(60000, 550000),
                                'stock' => rand(5, 50),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            DB::table('product_variant_values')->insert([
                                [
                                    'product_variant_id' => $variantId,
                                    'attribute_value_id' => $colorValueIds[$c],
                                ],
                                [
                                    'product_variant_id' => $variantId,
                                    'attribute_value_id' => $sizeValueIds[$s],
                                ]
                            ]);
                        }
                    }
                }

                if ($i % 500 == 0) {
                    $this->command->info("$i محصول ساخته شد...");
                }
            }

            $this->command->info('ساخت 2000 محصول با موفقیت کامل شد!');


            /*
            |--------------------------------------------------------------------------
            | 5) User + Address
            |--------------------------------------------------------------------------
            */
            $userId = DB::table('users')->where('email', 'test@example.com')->value('id');

            if (!$userId) {
                $userId = DB::table('users')->insertGetId([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'phone' => '09120000001',
                    'password' => Hash::make('password'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $addressId = DB::table('user_addresses')
                ->where('user_id', $userId)
                ->where('is_default', 1)
                ->value('id');

            if (!$addressId) {
                $addressId = DB::table('user_addresses')->insertGetId([
                    'user_id' => $userId,
                    'receiver_name' => 'Test User',
                    'receiver_phone' => '09123456789',
                    'province' => 'Tehran',
                    'city' => 'Tehran',
                    'address' => 'Test Address - Valiasr Street',
                    'postal_code' => '1234567890',
                    'is_default' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 6) Order + Order Items
            |--------------------------------------------------------------------------
            */
            $orderId = DB::table('orders')
                ->where('user_id', $userId)
                ->where('address_id', $addressId)
                ->value('id');

            if (!$orderId) {
                $orderId = DB::table('orders')->insertGetId([
                    'user_id' => $userId,
                    'address_id' => $addressId,
                    'status' => 'pending',
                    'total_amount' => 350000,
                    'final_amount' => 350000,
                    'shipping_amount' => 0,
                    'discount_amount' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $firstThreeProducts = array_slice($productIds, 0, 3);
                foreach ($firstThreeProducts as $pId) {
                    DB::table('order_items')->insert([
                        'order_id' => $orderId,
                        'product_id' => $pId,
                        'quantity' => 1,
                        'price' => 110000,
                        'total_price' => 110000,
                        'product_name' => "Product #$pId",
                        'sku' => "SKU-$pId",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }


            DB::commit();
            $this->command->info('✅ سیدر با موفقیت اجرا شد!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('خطا در اجرای سیدر: ' . $e->getMessage());
            throw $e;
        }
    }
}
