<script src="{{ asset('vendor/bootstrap/5.3.0/js/bootstrap.bundle.min.js') }}" defer></script>
@if (request()->routeIs('filament.admin.pages.dashboard'))
    @include('filament.hooks.dashboard-map-scripts')
@endif
<script src="{{ asset('vendor/sweetalert2/11/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('adminpanel/sweetalert-notifications.js') }}"></script>
<script src="{{ asset('adminpanel/script.js') }}" defer></script>
