<?php

namespace Database\Seeders;

use App\Services\Shop\CategoryTaxonomySeederService;
use Illuminate\Database\Seeder;

class CategoryTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        app(CategoryTaxonomySeederService::class)->run(dryRun: false);
    }
}
