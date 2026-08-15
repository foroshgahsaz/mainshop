<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        if (MenuItem::query()->exists()) {
            return;
        }

        $items = [
            ['label' => 'محصولات چاپینو', 'item_type' => MenuItem::TYPE_MEGA_TRIGGER, 'link_type' => 'route', 'link_value' => 'products.index', 'location' => MenuItem::LOCATION_DESKTOP, 'position' => 1],
            ['label' => 'صفحه اصلی', 'item_type' => MenuItem::TYPE_LINK, 'link_type' => 'route', 'link_value' => 'home', 'location' => MenuItem::LOCATION_BOTH, 'position' => 2],
            ['label' => 'لیست کالاها', 'item_type' => MenuItem::TYPE_LINK, 'link_type' => 'route', 'link_value' => 'products.index', 'location' => MenuItem::LOCATION_BOTH, 'position' => 3],
            ['label' => 'سوالی دارید؟', 'item_type' => MenuItem::TYPE_LINK, 'link_type' => 'page', 'link_value' => 'contact', 'location' => MenuItem::LOCATION_BOTH, 'position' => 4],
            ['label' => 'پیگیری سفارش', 'item_type' => MenuItem::TYPE_LINK, 'link_type' => 'route', 'link_value' => 'account.orders', 'location' => MenuItem::LOCATION_BOTH, 'position' => 5],
            ['label' => 'بلاگ', 'item_type' => MenuItem::TYPE_LINK, 'link_type' => 'route', 'link_value' => 'blog.index', 'location' => MenuItem::LOCATION_BOTH, 'position' => 6],
            ['label' => 'درباره ما', 'item_type' => MenuItem::TYPE_LINK, 'link_type' => 'page', 'link_value' => 'about', 'location' => MenuItem::LOCATION_BOTH, 'position' => 7],
            ['label' => 'فروش ویژه', 'item_type' => MenuItem::TYPE_MEGA_PROMO, 'link_type' => 'route', 'link_value' => 'products.index', 'location' => MenuItem::LOCATION_DESKTOP, 'position' => 8, 'mega_column' => 4],
        ];

        foreach ($items as $item) {
            MenuItem::query()->create($item + ['is_active' => true]);
        }
    }
}
