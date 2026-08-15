<?php

namespace App\Support;

class IranCityCoordinates
{
    /** @var array<string, array{0: float, 1: float}> */
    public const CITIES = [
        'تهران' => [35.6892, 51.3890],
        'اصفهان' => [32.6546, 51.6680],
        'مشهد' => [36.2970, 59.6060],
        'شیراز' => [29.5918, 52.5836],
        'تبریز' => [38.0800, 46.2919],
        'کرج' => [35.8360, 50.9930],
        'اهواز' => [31.3183, 48.6706],
        'قم' => [34.6416, 50.8746],
        'کرمان' => [30.2832, 57.0788],
        'رشت' => [37.2808, 49.5832],
        'زاهدان' => [29.4963, 60.8629],
        'همدان' => [34.7992, 48.5146],
        'یزد' => [31.8974, 54.3569],
        'اراک' => [34.0917, 49.6892],
        'اردبیل' => [38.2498, 48.2933],
        'بندرعباس' => [27.1865, 56.2808],
        'کرمانشاه' => [34.3142, 47.0650],
        'ساری' => [36.5633, 53.0601],
        'گرگان' => [36.8427, 54.4319],
        'سنندج' => [35.3219, 46.9862],
    ];

    public static function resolve(?string $city): ?array
    {
        if (blank($city)) {
            return null;
        }

        $city = trim($city);

        if (isset(self::CITIES[$city])) {
            return self::CITIES[$city];
        }

        foreach (self::CITIES as $name => $coords) {
            if (str_contains($city, $name) || str_contains($name, $city)) {
                return $coords;
            }
        }

        return null;
    }
}
