<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\ProductPlaceholder;
use Illuminate\Database\Seeder;

class SampleProductsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('در حال ساخت ۲ محصول کامل نمونه...');

        $categoryClothing = Category::updateOrCreate(
            ['slug' => 'clothing'],
            ['name' => 'پوشاک', 'is_active' => true, 'position' => 1]
        );

        $categoryShoes = Category::updateOrCreate(
            ['slug' => 'shoes'],
            ['name' => 'کفش', 'is_active' => true, 'position' => 2]
        );

        $brandChapino = Brand::updateOrCreate(
            ['slug' => 'chapino'],
            [
                'name' => 'چاپینو',
                'description' => 'برند اختصاصی فروشگاه چاپینو',
                'is_active' => true,
                'position' => 1,
            ]
        );

        $brandSnowa = Brand::updateOrCreate(
            ['slug' => 'snowa'],
            [
                'name' => 'اسنوا',
                'description' => 'برند معتبر پوشاک و کفش',
                'is_active' => true,
                'position' => 2,
            ]
        );

        $colorAttr = Attribute::updateOrCreate(
            ['slug' => 'color'],
            ['name' => 'رنگ', 'type' => 'select', 'position' => 1, 'is_active' => true]
        );

        $sizeAttr = Attribute::updateOrCreate(
            ['slug' => 'size'],
            ['name' => 'سایز', 'type' => 'select', 'position' => 2, 'is_active' => true]
        );

        $fabricAttr = Attribute::updateOrCreate(
            ['slug' => 'fabric'],
            ['name' => 'جنس پارچه', 'type' => 'select', 'position' => 3, 'is_active' => true]
        );

        $soleAttr = Attribute::updateOrCreate(
            ['slug' => 'sole'],
            ['name' => 'نوع کفی', 'type' => 'select', 'position' => 3, 'is_active' => true]
        );

        $colors = $this->upsertValues($colorAttr, [
            'navy' => 'سرمه‌ای',
            'black' => 'مشکی',
            'white' => 'سفید',
        ]);

        $sizes = $this->upsertValues($sizeAttr, [
            'm' => 'M',
            'l' => 'L',
            'xl' => 'XL',
        ]);

        $fabrics = $this->upsertValues($fabricAttr, [
            'cotton' => 'نخی',
            'poly' => 'پلی‌استر',
            'blend' => 'مخلوط',
        ]);

        $soles = $this->upsertValues($soleAttr, [
            'soft' => 'نرم',
            'firm' => 'سخت',
            'ortho' => 'ارگونومیک',
        ]);

        $this->seedProductWithCombinations(
            slug: 'classic-mens-shirt',
            data: [
                'name' => 'پیراهن مردانه کلاسیک',
                'category_id' => $categoryClothing->id,
                'brand_id' => $brandChapino->id,
                'price' => 890000,
                'sale_price' => 749000,
                'stock' => 45,
                'sku' => 'SHIRT-001',
                'short_description' => 'پیراهن مردانه با پارچه نخی درجه یک، مناسب محیط کار و مهمانی.',
                'description' => 'پیراهن مردانه کلاسیک چاپینو با دوخت مرغوب، یقه استاندارد و رنگ‌بندی متنوع. قابل شستشو با ماشین لباسشویی.',
                'weight' => 320,
                'is_featured' => true,
                'meta_title' => 'پیراهن مردانه کلاسیک | چاپینو',
                'meta_description' => 'خرید پیراهن مردانه کلاسیک با قیمت مناسب، ارسال سریع و ضمانت اصالت کالا.',
            ],
            attributes: [$colorAttr, $sizeAttr, $fabricAttr],
            valueGroups: [$colors, $sizes, $fabrics],
            skuPrefix: 'SHIRT-001',
            basePrice: 890000,
            baseSale: 749000,
            priceStep: 15000,
            imagePrefix: 'products/variants/shirt',
        );

        $this->seedProductWithCombinations(
            slug: 'leather-sport-shoe',
            data: [
                'name' => 'کفش چرم ورزشی مردانه',
                'category_id' => $categoryShoes->id,
                'brand_id' => $brandSnowa->id,
                'price' => 2450000,
                'sale_price' => 2190000,
                'stock' => 30,
                'sku' => 'SHOE-001',
                'short_description' => 'کفش چرم طبیعی با کفی ارگونومیک، مناسب پیاده‌روی روزانه.',
                'description' => 'کفش چرم ورزشی مردانه با طراحی مدرن، دوام بالا و راحتی استثنایی. مناسب فصل پاییز و زمستان.',
                'weight' => 780,
                'is_featured' => true,
                'meta_title' => 'کفش چرم ورزشی مردانه | چاپینو',
                'meta_description' => 'خرید کفش چرم مردانه با کیفیت بالا، رنگ و سایز متنوع.',
            ],
            attributes: [$colorAttr, $sizeAttr, $soleAttr],
            valueGroups: [$colors, $sizes, $soles],
            skuPrefix: 'SHOE-001',
            basePrice: 2450000,
            baseSale: 2190000,
            priceStep: 45000,
            imagePrefix: 'products/variants/shoe',
        );

        $this->command?->info('✅ ۲ محصول نمونه با ۲۷ واریانت (۳×۳×۳) ساخته شد.');
    }

    /**
     * @param  array<string, AttributeValue>  $labels
     * @return array<string, AttributeValue>
     */
    protected function upsertValues(Attribute $attribute, array $labels): array
    {
        $values = [];
        $position = 1;

        foreach ($labels as $key => $label) {
            $values[$key] = AttributeValue::updateOrCreate(
                ['attribute_id' => $attribute->id, 'value' => $label],
                ['position' => $position++, 'is_active' => true]
            );
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, Attribute>  $attributes
     * @param  array<int, array<string, AttributeValue>>  $valueGroups
     */
    protected function seedProductWithCombinations(
        string $slug,
        array $data,
        array $attributes,
        array $valueGroups,
        string $skuPrefix,
        int $basePrice,
        int $baseSale,
        int $priceStep,
        string $imagePrefix,
    ): void {
        $product = Product::updateOrCreate(
            ['slug' => $slug],
            array_merge($data, [
                'is_active' => true,
                'views' => random_int(100, 2000),
                'robots' => 'index,follow',
            ])
        );

        $sync = [];
        foreach ($attributes as $index => $attribute) {
            $sync[$attribute->id] = [
                'is_required' => true,
                'is_variation' => true,
                'position' => $index + 1,
            ];
        }
        $product->attributes()->sync($sync);

        $primaryImage = 'products/sample-'.$slug.'.jpg';
        $secondaryImage = 'products/sample-'.$slug.'-2.jpg';
        ProductPlaceholder::ensure($primaryImage, $product->name);
        ProductPlaceholder::ensure($secondaryImage, $product->name.' 2');

        ProductImage::updateOrCreate(
            ['product_id' => $product->id, 'position' => 0],
            [
                'image' => $primaryImage,
                'is_primary' => true,
                'position' => 0,
            ]
        );

        ProductImage::updateOrCreate(
            ['product_id' => $product->id, 'position' => 1],
            [
                'image' => $secondaryImage,
                'is_primary' => false,
                'position' => 1,
            ]
        );

        $combinations = $this->cartesianProduct($valueGroups);
        $index = 0;

        foreach ($combinations as $combo) {
            $keys = array_keys($combo);
            $labels = array_map(fn (AttributeValue $v) => $v->value, $combo);
            $name = implode(' / ', $labels);
            $sku = $skuPrefix.'-'.strtoupper(implode('-', $keys));
            $price = $basePrice + ($index * $priceStep);
            $sale = $baseSale + (int) ($index * $priceStep * 0.85);
            $image = $imagePrefix.'-'.implode('-', $keys).'.jpg';

            $variant = ProductVariant::updateOrCreate(
                ['product_id' => $product->id, 'sku' => $sku],
                [
                    'name' => $name,
                    'price' => $price,
                    'sale_price' => $sale,
                    'stock' => max(3, 20 - ($index % 15)),
                    'weight' => $data['weight'] ?? null,
                    'image' => $image,
                    'is_active' => true,
                ]
            );

            $variant->attributeValues()->sync(
                collect($combo)->map(fn (AttributeValue $v) => $v->id)->values()->all()
            );

            $index++;
        }
    }

    /**
     * @param  array<int, array<string, AttributeValue>>  $groups
     * @return array<int, array<string, AttributeValue>>
     */
    protected function cartesianProduct(array $groups): array
    {
        $result = [[]];

        foreach ($groups as $group) {
            $append = [];
            foreach ($result as $product) {
                foreach ($group as $key => $value) {
                    $append[] = $product + [$key => $value];
                }
            }
            $result = $append;
        }

        return $result;
    }
}
