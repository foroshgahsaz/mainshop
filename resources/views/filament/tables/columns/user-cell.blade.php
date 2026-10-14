@php
    /** @var \App\Models\User $record */
    $record = $getRecord();
    $avatarUrl = filled($record->avatar)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->avatar)
        : 'https://ui-avatars.com/api/?name='.urlencode($record->name ?? 'کاربر').'&background=7239ea&color=fff&size=128&rounded=false';
@endphp

<div class="fi-admin-user-cell">
    <img src="{{ $avatarUrl }}"
         alt=""
         class="fi-admin-user-cell__avatar"
         width="40"
         height="40"
         loading="lazy">
    <div class="fi-admin-user-cell__text">
        <span class="fi-admin-user-cell__name">{{ $record->name }}</span>
        @if (filled($record->phone))
            <span class="fi-admin-user-cell__phone" dir="ltr">{{ $record->phone }}</span>
        @endif
    </div>
</div>
