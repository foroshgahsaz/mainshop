<?php

namespace App\Services\Shop;

/**
 * درخت دسته‌بندی فروشگاه (مگا منو: ریشه → ستون → آیتم).
 *
 * @phpstan-type TaxonomyNode array{name: string, children?: list<TaxonomyNode>}
 */
final class CategoryTaxonomyBlueprint
{
    /** @return list<TaxonomyNode> */
    public static function tree(): array
    {
        return [
            ['name' => 'لوازم خانگی'],
            ['name' => 'لوازم ورزشی'],
            [
                'name' => 'مبلمان ، فرش ، لوستر',
                'children' => [
                    ['name' => 'مبلمان'],
                    ['name' => 'فرش'],
                    ['name' => 'لوستر'],
                ],
            ],
            ['name' => 'کالای دیجیتال'],
            [
                'name' => 'لوازم آشپزخانه',
                'children' => [
                    ['name' => 'بند رخت'],
                    ['name' => 'آبچکان'],
                    ['name' => 'سبد سیب زمینی پیاز'],
                ],
            ],
            [
                'name' => 'چینی ، بلور ، اپال',
                'children' => [
                    [
                        'name' => 'سرویس چینی',
                        'children' => [
                            ['name' => 'سرویس غذاخوری'],
                        ],
                    ],
                    [
                        'name' => 'بلور',
                        'children' => [
                            ['name' => 'ظروف پذیرایی'],
                        ],
                    ],
                    ['name' => 'اپال'],
                ],
            ],
            ['name' => 'کالای خواب'],
            ['name' => 'شیر آلات'],
            [
                'name' => 'سرمایشی، گرمایشی',
                'children' => [
                    ['name' => 'سرمایشی'],
                    ['name' => 'گرمایشی'],
                ],
            ],
        ];
    }
}
