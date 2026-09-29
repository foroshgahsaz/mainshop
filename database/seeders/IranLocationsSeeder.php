<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IranLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/iran_locations.php');

        foreach ($data['provinces'] as $position => $provinceRow) {
            $province = Province::query()->updateOrCreate(
                ['slug' => $provinceRow['slug']],
                [
                    'name' => $provinceRow['name'],
                    'position' => $position,
                ]
            );

            $cityNames = $data['cities'][$provinceRow['slug']] ?? [$provinceRow['name']];

            foreach ($cityNames as $cityPosition => $cityName) {
                $citySlug = Str::slug($cityName.'-'.$provinceRow['slug']);

                City::query()->updateOrCreate(
                    [
                        'province_id' => $province->id,
                        'slug' => $citySlug,
                    ],
                    [
                        'name' => $cityName,
                        'position' => $cityPosition,
                    ]
                );
            }
        }
    }
}
