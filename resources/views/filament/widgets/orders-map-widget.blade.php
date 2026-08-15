<x-filament-widgets::widget>
    <x-filament::section heading="نقشه مقصد سفارش‌ها" icon="heroicon-o-map">
        <div wire:ignore
             id="ordersMap"
             class="orders-map-widget"
             data-points='@json($this->getMapPoints())'
             style="height: 400px; min-height: 400px; border-radius: 8px; background: #e8eef4;"></div>
    </x-filament::section>
</x-filament-widgets::widget>
