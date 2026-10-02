@php
    use App\Filament\Representative\Pages\CreateDraftOrder;
    use App\Filament\Representative\Resources\CustomerResource;
    use App\Filament\Representative\Resources\DraftOrderResource;

    $user = filament()->auth()->user();
    $brand = filament()->getBrandName();
    $isDashboard = request()->routeIs('filament.representative.pages.dashboard');
    $profileUrl = route('account.dashboard');
    $dashboardUrl = filament()->getUrl();

    $panels = [
        'customers' => [
            'label' => 'مشتریان',
            'icon' => 'fa-users',
            'routes' => ['filament.representative.resources.customers.*'],
            'menus' => [
                ['label' => 'مشتریان', 'icon' => 'fa-users', 'items' => [
                    ['label' => 'لیست مشتریان', 'url' => CustomerResource::getUrl('index'), 'icon' => 'fa-list'],
                    ['label' => 'افزودن مشتری', 'url' => CustomerResource::getUrl('create'), 'icon' => 'fa-plus'],
                ]],
            ],
        ],
        'orders' => [
            'label' => 'پیش‌سفارش',
            'icon' => 'fa-shopping-bag',
            'routes' => [
                'filament.representative.resources.draft-orders.*',
                'filament.representative.pages.create-draft-order',
            ],
            'menus' => [
                ['label' => 'سفارش', 'icon' => 'fa-shopping-bag', 'items' => [
                    ['label' => 'سفارش جدید', 'url' => CreateDraftOrder::getUrl(), 'icon' => 'fa-plus'],
                    ['label' => 'پیش‌سفارش‌ها', 'url' => DraftOrderResource::getUrl('index'), 'icon' => 'fa-list'],
                ]],
            ],
        ],
    ];

    $activePanel = 'customers';
    $sidebarPanel = 'customers';
    if ($isDashboard) {
        $activePanel = 'dashboard';
        $sidebarPanel = 'customers';
    } else {
        foreach ($panels as $panelId => $panel) {
            if (collect($panel['routes'])->contains(fn ($p) => request()->routeIs($p))) {
                $activePanel = $panelId;
                $sidebarPanel = $panelId;
                break;
            }
        }
    }

    $activePanelLabel = $isDashboard ? 'داشبورد نمایندگی' : ($panels[$sidebarPanel]['label'] ?? 'منو');

    $navIcons = [
        ['id' => 'dashboard', 'icon' => 'fa-chart-pie', 'tooltip' => 'داشبورد', 'href' => $dashboardUrl, 'isLink' => true],
        ['id' => 'customers', 'icon' => 'fa-users', 'tooltip' => 'مشتریان', 'panel' => 'customers'],
        ['id' => 'orders', 'icon' => 'fa-shopping-bag', 'tooltip' => 'پیش‌سفارش', 'panel' => 'orders'],
    ];

    $isMenuItemActive = function (string $url): bool {
        $current = url()->current();
        $target = rtrim($url, '/');

        return $current === $target || str_starts_with($current, $target.'/');
    };

    $initial = mb_substr($user?->name ?? 'ن', 0, 1);
@endphp

<div class="sidebar-secondary">
    @foreach ($navIcons as $navIcon)
        @if ($navIcon['isLink'] ?? false)
            <a href="{{ $navIcon['href'] }}"
               class="sidebar-icon-item sidebar-icon-link {{ $activePanel === 'dashboard' ? 'active' : '' }}"
               data-tooltip="{{ $navIcon['tooltip'] }}"
               aria-label="{{ $navIcon['tooltip'] }}">
                <i class="fas {{ $navIcon['icon'] }}"></i>
                <span class="sidebar-icon-label">{{ $navIcon['tooltip'] }}</span>
            </a>
        @else
            <div class="sidebar-icon-item {{ $activePanel === $navIcon['id'] ? 'active' : '' }}"
                 data-panel="{{ $navIcon['panel'] }}"
                 data-tooltip="{{ $navIcon['tooltip'] }}"
                 role="button"
                 tabindex="0"
                 aria-label="{{ $navIcon['tooltip'] }}">
                <i class="fas {{ $navIcon['icon'] }}"></i>
                <span class="sidebar-icon-label">{{ $navIcon['tooltip'] }}</span>
            </div>
        @endif
        @if ($loop->first)
            <div class="sidebar-divider"></div>
        @endif
    @endforeach

    <a href="{{ $profileUrl }}"
       class="sidebar-user-icon d-flex align-items-center justify-content-center text-white fw-bold text-decoration-none"
       style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); font-size: 14px;"
       data-tooltip="حساب فروشگاه: {{ $user?->name ?? 'نماینده' }}"
       aria-label="حساب کاربری فروشگاه">{{ $initial }}</a>
</div>

<div class="sidebar-primary" id="sidebarPrimary">
    <div class="sidebar-header">
        <p class="sidebar-title">پنل نمایندگی</p>
        <p class="mb-0 fw-bold text-dark" id="sidebarPanelTitle">{{ $activePanelLabel }}</p>
    </div>

    <div class="sidebar-menu" id="sidebarMenu">
        @foreach ($panels as $panelId => $panel)
            <div class="menu-content {{ $sidebarPanel === $panelId ? 'active' : '' }}" id="{{ $panelId }}Content">
                @foreach ($panel['menus'] as $menuIndex => $menu)
                    @php
                        $submenuId = $panelId.'Submenu'.$menuIndex;
                        $hasActiveChild = collect($menu['items'])->contains(fn ($item) => $isMenuItemActive($item['url']));
                    @endphp
                    <button type="button"
                            class="menu-item {{ $hasActiveChild ? 'active' : '' }}"
                            data-submenu="{{ $submenuId }}"
                            aria-expanded="{{ $hasActiveChild ? 'true' : 'false' }}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="menu-icon"><i class="fas {{ $menu['icon'] }}"></i></span>
                            <span>{{ $menu['label'] }}</span>
                        </div>
                        <i class="fas fa-chevron-down menu-arrow" style="transform: rotate({{ $hasActiveChild ? '180' : '0' }}deg);"></i>
                    </button>
                    <div class="submenu {{ $hasActiveChild ? 'expanded' : '' }}" id="{{ $submenuId }}">
                        @foreach ($menu['items'] as $item)
                            <a href="{{ $item['url'] }}"
                               class="submenu-item {{ $isMenuItemActive($item['url']) ? 'active' : '' }}">
                                <i class="fas {{ $item['icon'] }} ms-2 submenu-item-icon"></i>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>

<div class="toggle-sidebar-btn" onclick="toggleSidebar()">
    <i class="fas fa-chevron-left" id="toggleIcon"></i>
</div>

<script>
    window.__adminPanelTitles = @json(collect($panels)->mapWithKeys(fn ($p, $id) => [$id => $p['label']]));
</script>
