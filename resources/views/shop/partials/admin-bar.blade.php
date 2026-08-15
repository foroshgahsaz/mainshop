@php
    use App\Filament\Resources\CategoryResource;
    use App\Filament\Resources\PostResource;
    use App\Filament\Resources\ProductResource;
    use App\Filament\Resources\UserResource;
    use App\Models\Post;
    use App\Models\Product;

    $adminUser = auth()->user();
    $currentProduct = request()->route('product');
    $currentPost = request()->route('post');

    $createLinks = [
        ['label' => 'محصول', 'url' => ProductResource::getUrl('create'), 'icon' => 'fa-box-open'],
        ['label' => 'دسته‌بندی', 'url' => CategoryResource::getUrl('create'), 'icon' => 'fa-tags'],
        ['label' => 'کاربر', 'url' => UserResource::getUrl('create'), 'icon' => 'fa-user-plus'],
        ['label' => 'مقاله', 'url' => PostResource::getUrl('create'), 'icon' => 'fa-file-alt'],
        ['label' => 'اسلایدر', 'url' => \App\Filament\Resources\HomeSliderResource::getUrl('create'), 'icon' => 'fa-images'],
    ];
@endphp

<div id="storeAdminBar" class="store-admin-bar">
    <div class="store-admin-bar-inner">
        <div class="store-admin-bar-links">
            <a href="{{ url('/admin') }}" class="store-admin-bar-link">
                <i class="fas fa-chart-pie"></i>
                <span>مشاهده داشبورد</span>
            </a>

            @if ($currentProduct instanceof Product)
                <a href="{{ ProductResource::getUrl('edit', ['record' => $currentProduct]) }}" class="store-admin-bar-link">
                    <i class="fas fa-edit"></i>
                    <span>ویرایش این محصول</span>
                </a>
            @endif

            @if ($currentPost instanceof Post)
                <a href="{{ PostResource::getUrl('edit', ['record' => $currentPost]) }}" class="store-admin-bar-link">
                    <i class="fas fa-edit"></i>
                    <span>ویرایش این مقاله</span>
                </a>
            @endif

            <div class="store-admin-bar-dropdown">
                <button type="button" class="store-admin-bar-link store-admin-bar-dropdown-toggle" aria-expanded="false">
                    <i class="fas fa-plus-circle"></i>
                    <span>افزودن</span>
                    <i class="fas fa-chevron-down store-admin-bar-chevron"></i>
                </button>
                <div class="store-admin-bar-submenu">
                    @foreach ($createLinks as $link)
                        <a href="{{ $link['url'] }}" class="store-admin-bar-submenu-item">
                            <i class="fas {{ $link['icon'] }}"></i>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="store-admin-bar-user">
            <i class="fas fa-user-shield"></i>
            <span>{{ $adminUser->name ?? 'مدیر' }}</span>
        </div>
    </div>
</div>

<style>
    .store-admin-bar {
        background: #1e1e2d;
        color: #fff;
        font-family: YekanBakh, Yekan, Tahoma, sans-serif;
        font-size: 13px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .store-admin-bar-inner {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 16px;
        min-height: 42px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .store-admin-bar-links {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
    }

    .store-admin-bar-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #fff;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 6px;
        border: none;
        background: transparent;
        cursor: pointer;
        font: inherit;
        white-space: nowrap;
    }

    .store-admin-bar-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
    }

    .store-admin-bar-user {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #a1a5b7;
        white-space: nowrap;
    }

    .store-admin-bar-dropdown {
        position: relative;
    }

    .store-admin-bar-chevron {
        font-size: 10px;
        opacity: 0.8;
    }

    .store-admin-bar-submenu {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        right: 0;
        min-width: 180px;
        background: #2b2b40;
        border-radius: 8px;
        padding: 6px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    }

    .store-admin-bar-dropdown.open .store-admin-bar-submenu {
        display: block;
    }

    .store-admin-bar-dropdown.open .store-admin-bar-chevron {
        transform: rotate(180deg);
    }

    .store-admin-bar-submenu-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
    }

    .store-admin-bar-submenu-item:hover {
        background: rgba(114, 57, 234, 0.25);
        color: #fff;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropdown = document.querySelector('.store-admin-bar-dropdown');
        if (!dropdown) return;

        const toggle = dropdown.querySelector('.store-admin-bar-dropdown-toggle');

        toggle?.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('open');
        });

        document.addEventListener('click', function () {
            dropdown.classList.remove('open');
        });
    });
</script>
