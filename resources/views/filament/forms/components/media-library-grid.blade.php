@php
    $files = $files ?? collect();
    $directoryLabel = $directoryLabel ?? 'فایل‌ها';
    $statePath = $getStatePath();
    $filesPayload = $files->map(fn ($file) => [
        'path' => $file->path,
        'alt_text' => $file->alt_text,
        'title' => $file->title,
    ])->values();
@endphp

<div
    class="media-library-grid"
    x-data="{
        files: @js($filesPayload),
        selected: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
        pick(path) {
            this.selected = path
            const file = this.files.find((item) => item.path === path)
            if (!file) {
                return
            }
            const root = @js(str($statePath)->beforeLast('.')->toString());
            if (file.alt_text) {
                $wire.set(root + '.alt_text', file.alt_text, false)
            }
            if (file.title) {
                $wire.set(root + '.title', file.title, false)
            }
        },
    }"
>
    <div class="media-library-grid__toolbar">
        <div>
            <strong>مرکز فایل</strong>
            <span class="media-library-grid__count">{{ $files->count() }} فایل در «{{ $directoryLabel }}»</span>
        </div>
        <span class="media-library-grid__tip">روی تصویر کلیک کنید تا انتخاب شود</span>
    </div>

    @if ($files->isEmpty())
        <div class="media-library-grid__empty">
            <x-filament::icon icon="heroicon-o-photo" class="h-10 w-10" />
            <p>هنوز فایلی در این پوشه نیست.</p>
            <p class="media-library-grid__empty-sub">به تب «بارگذاری» بروید و فایل جدید آپلود کنید.</p>
        </div>
    @else
        <div class="media-library-grid__items">
            @foreach ($files as $file)
                <button
                    type="button"
                    class="media-library-grid__item"
                    :class="{ 'is-selected': selected === @js($file->path) }"
                    x-on:click="pick(@js($file->path))"
                >
                    <span class="media-library-grid__thumb">
                        @if ($file->url)
                            <img src="{{ $file->url }}" alt="{{ $file->alt_text ?: $file->name }}" loading="lazy">
                        @endif
                        <span class="media-library-grid__check">
                            <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                        </span>
                    </span>
                    <span class="media-library-grid__name" title="{{ $file->name }}">{{ $file->name }}</span>
                    @if ($file->title || $file->alt_text)
                        <span class="media-library-grid__seo-badge">SEO</span>
                    @endif
                </button>
            @endforeach
        </div>
    @endif
</div>
