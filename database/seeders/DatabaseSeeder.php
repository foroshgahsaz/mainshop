<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['phone' => '09120000000'],
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'change-me')),
                'status' => true,
                'is_admin' => true,
            ]
        );

        if (! app()->environment('testing')) {
            $this->call(ShopDemoSeeder::class);
            $this->call(SampleProductsSeeder::class);
            $this->call(SampleOrdersSeeder::class);
            $this->call(StaticPagesSeeder::class);
            $this->call(RichContentSeeder::class);
            $this->call(MenuItemSeeder::class);

            if (filter_var(env('SEED_HEAVY', false), FILTER_VALIDATE_BOOLEAN)) {
                $this->call(TestShopSeeder::class);
            }
        }
    }
}
