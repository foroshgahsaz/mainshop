@php
    use App\Filament\Resources\UserResource;
    use App\Models\User;

    $record = $record ?? null;
    $isEdit = $record instanceof User;
    $baseUrl = $isEdit
        ? UserResource::getUrl('edit', ['record' => $record])
        : UserResource::getUrl('create');
@endphp

<nav class="user-edit-tabs" aria-label="بخش‌های ویرایش کاربر">
    @foreach ($tabs as $key => $tab)
        <a href="{{ $baseUrl }}?tab={{ $key }}"
           wire:navigate="false"
           @class(['user-edit-tabs__link', 'user-edit-tabs__link--active' => $activeTab === $key])>
            <i class="fas {{ $tab['icon'] }} user-edit-tabs__icon" aria-hidden="true"></i>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
