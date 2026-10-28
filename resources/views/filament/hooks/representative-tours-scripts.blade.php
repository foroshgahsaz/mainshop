@php
    use App\Filament\Representative\Pages\CreateDraftOrder;
    use App\Filament\Representative\Pages\Dashboard;
    use App\Filament\Representative\Resources\CustomerResource;
    use App\Filament\Representative\Resources\DraftOrderResource;

    $panel = filament()->getCurrentPanel();
    $isRepPanel = $panel?->getId() === 'representative';
@endphp
@if ($isRepPanel && auth()->check())
    @php
        $tourAssetV = @filemtime(public_path('representative/rep-panel-tours.js')) ?: 1;
        $routes = [
            'dashboard' => Dashboard::getUrl(panel: 'representative'),
            'customers' => CustomerResource::getUrl('index', panel: 'representative'),
            'createOrder' => CreateDraftOrder::getUrl(panel: 'representative'),
            'draftOrders' => DraftOrderResource::getUrl('index', panel: 'representative'),
            'sampleProforma' => null,
        ];
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/intro.js@7.2.0/intro.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('representative/rep-panel-tours.js') }}?v={{ $tourAssetV }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.RepPanelTours === 'undefined') {
                return;
            }
            window.RepPanelTours.init({
                userId: @json(auth()->id()),
                basePath: @json('/'.trim($panel->getPath(), '/')),
                routes: @json($routes),
            });
        });
    </script>
@endif
