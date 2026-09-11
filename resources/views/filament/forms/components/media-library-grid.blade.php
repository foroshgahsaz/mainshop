@php
    $files = $files ?? collect();
    $directoryLabel = $directoryLabel ?? 'فایل‌ها';
    $statePath = $getStatePath();
    $formRoot = str($statePath)->beforeLast('.')->toString();
    $selectedStatePath = $formRoot.'.selected_path';
    $wireKey = md5($statePath.'|'.$directory);
@endphp

<div
    class="media-library-grid-host"
    x-data="{
        formRoot: @js($formRoot),
        selected: $wire.$entangle(@js($selectedStatePath)).live,
    }"
    x-on:media-library-picked.window="
        if ($event.detail.formRoot !== formRoot) return;
        selected = $event.detail.path;
        $wire.set(formRoot + '.selected_path', $event.detail.path);
        if ($event.detail.altText) $wire.set(formRoot + '.alt_text', $event.detail.altText);
        if ($event.detail.title) $wire.set(formRoot + '.title', $event.detail.title);
    "
    x-on:media-library-deleted.window="
        if ($event.detail.formRoot !== formRoot) return;
        if ($event.detail.paths.includes(selected)) {
            selected = null;
            $wire.set(formRoot + '.alt_text', null);
            $wire.set(formRoot + '.title', null);
        }
    "
>
    <p class="media-library-grid__selection" x-show="selected" x-cloak>
        انتخاب‌شده: <span x-text="selected"></span>
    </p>

    @livewire(\App\Livewire\Admin\MediaLibraryGrid::class, [
        'directory' => $directory,
        'formRoot' => $formRoot,
        'wireKey' => $wireKey,
    ], key('media-library-'.$wireKey))
</div>
