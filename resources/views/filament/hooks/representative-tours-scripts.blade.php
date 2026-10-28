@php
    use App\Filament\Representative\Pages\CreateDraftOrder;
    use App\Filament\Representative\Pages\Dashboard;
    use App\Filament\Representative\Resources\CustomerResource;
    use App\Filament\Representative\Resources\DraftOrderResource;

    $isRepApp = request()->routeIs('filament.representative.*') && ! request()->routeIs('filament.representative.auth.*');
@endphp
@if ($isRepApp && auth()->check())
    @php
        $tourAssetV = @filemtime(public_path('representative/rep-panel-tours.js')) ?: 1;
        $routes = [
            'dashboard' => Dashboard::getUrl(panel: 'representative'),
            'customers' => CustomerResource::getUrl('index', panel: 'representative'),
            'createOrder' => CreateDraftOrder::getUrl(panel: 'representative'),
            'draftOrders' => DraftOrderResource::getUrl('index', panel: 'representative'),
            'sampleProforma' => null,
        ];
        $bootConfig = [
            'userId' => auth()->id(),
            'basePath' => '/representative',
            'routes' => $routes,
        ];
    @endphp
    <script src="{{ asset('representative/intro.min.js') }}?v=7.2.0"></script>
    <script src="{{ asset('representative/rep-panel-tours.js') }}?v={{ $tourAssetV }}"></script>
    <script>
        (function () {
            var config = @json($bootConfig);

            function bootRepTours() {
                if (typeof window.RepPanelTours === 'undefined') {
                    return;
                }
                window.RepPanelTours.init(config);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootRepTours);
            } else {
                bootRepTours();
            }

            document.addEventListener('livewire:navigated', function () {
                if (typeof window.RepPanelTours !== 'undefined') {
                    window.RepPanelTours.init(config);
                }
            });
        })();
    </script>
@endif
