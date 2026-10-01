<?php

namespace App\Filament\Widgets;

use App\Support\IranCityCoordinates;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrdersMapWidget extends Widget
{
    protected static string $view = 'filament.widgets.orders-map-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'مقصد سفارش‌ها';

    /**
     * @return array<int, array{name: string, lat: float, lng: float, count: int, color: string}>
     */
    public function getMapPoints(): array
    {
        return Cache::remember('admin:dashboard:orders_map_points', 600, function (): array {
            return $this->buildMapPoints();
        });
    }

    /**
     * @return array<int, array{name: string, lat: float, lng: float, count: int, color: string}>
     */
    protected function buildMapPoints(): array
    {
        $colors = ['#7239ea', '#3699ff', '#ffc700', '#50cd89', '#f1416c'];

        $counts = DB::table('orders')
            ->join('user_addresses', 'orders.address_id', '=', 'user_addresses.id')
            ->whereNotNull('orders.address_id')
            ->whereNotNull('user_addresses.city')
            ->where('user_addresses.city', '!=', '')
            ->selectRaw('user_addresses.city as city, COUNT(*) as aggregate')
            ->groupBy('user_addresses.city')
            ->orderByDesc('aggregate')
            ->limit(40)
            ->pluck('aggregate', 'city')
            ->all();

        $points = [];
        $index = 0;

        foreach ($counts as $city => $count) {
            $coords = IranCityCoordinates::resolve((string) $city);

            if (! $coords) {
                continue;
            }

            $points[] = [
                'name' => (string) $city,
                'lat' => $coords[0],
                'lng' => $coords[1],
                'count' => (int) $count,
                'color' => $colors[$index % count($colors)],
            ];

            $index++;
        }

        if ($points === []) {
            foreach (array_slice(IranCityCoordinates::CITIES, 0, 5, true) as $name => $coords) {
                $points[] = [
                    'name' => $name,
                    'lat' => $coords[0],
                    'lng' => $coords[1],
                    'count' => 0,
                    'color' => $colors[$index % count($colors)],
                ];
                $index++;
            }
        }

        return $points;
    }
}
