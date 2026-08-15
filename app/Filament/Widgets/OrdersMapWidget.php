<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Support\IranCityCoordinates;
use Filament\Widgets\Widget;

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
        $colors = ['#7239ea', '#3699ff', '#ffc700', '#50cd89', '#f1416c'];
        $counts = [];

        Order::query()
            ->with('address:id,city')
            ->whereNotNull('address_id')
            ->latest()
            ->limit(500)
            ->get()
            ->each(function (Order $order) use (&$counts) {
                $city = $order->address?->city;

                if (blank($city)) {
                    return;
                }

                $counts[$city] = ($counts[$city] ?? 0) + 1;
            });

        arsort($counts);

        $points = [];
        $index = 0;

        foreach ($counts as $city => $count) {
            $coords = IranCityCoordinates::resolve($city);

            if (! $coords) {
                continue;
            }

            $points[] = [
                'name' => $city,
                'lat' => $coords[0],
                'lng' => $coords[1],
                'count' => $count,
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
