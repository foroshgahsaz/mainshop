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
    <span class="fi-admin-user-cell__name">{{ $record->name }}</span>
</div>
