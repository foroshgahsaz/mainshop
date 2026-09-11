@php
    $files = $files ?? collect();
    $directoryLabel = $directoryLabel ?? 'فایل‌ها';
    $statePath = $getStatePath();
    $formRoot = str($statePath)->beforeLast('.')->toString();
    $wireKey = md5($statePath.'|'.$directory);
@endphp

<div
    class="media-library-grid-host"
    x-data="{
        formRoot: @js($formRoot),
        selected: @entangle($statePath).live,
    }"
    x-on:media-library-picked.window="
        if ($event.detail.formRoot !== formRoot) return;
        selected = $event.detail.path;
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
    @livewire(\App\Livewire\Admin\MediaLibraryGrid::class, [
        'directory' => $directory,
        'formRoot' => $formRoot,
        'wireKey' => $wireKey,
    ], key('media-library-'.$wireKey))
</div>
