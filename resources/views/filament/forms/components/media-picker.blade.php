@php
    $statePath = $getStatePath();
    $state = $getState();
    $currentPath = collect(is_array($state) ? $state : [])
        ->first(fn ($value) => is_string($value) && $value !== '');
    $previewUrl = $currentPath ? \App\Support\ShopMedia::url($currentPath) : null;
    $libraryFiles = $field->getLibraryFiles()->map(fn ($file) => [
        'id' => $file->id ?? $file->path,
        'path' => $file->path,
        'url' => $file->url(),
        'name' => $file->original_name ?: basename($file->path),
        'size' => $file->humanSize(),
    ])->values();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    label-tag="div"
>
    <div
        x-data="{
            open: false,
            tab: 'library',
            search: '',
            selectedPath: @js($currentPath),
            files: @js($libraryFiles),
            get filteredFiles() {
                if (! this.search) {
                    return this.files;
                }

                const query = this.search.toLowerCase();

                return this.files.filter((file) =>
                    file.name.toLowerCase().includes(query) ||
                    file.path.toLowerCase().includes(query)
                );
            },
            get uploadedPath() {
                const state = $wire.get(@js($statePath));

                if (! state || typeof state !== 'object') {
                    return null;
                }

                return Object.values(state).find((value) => typeof value === 'string' && value !== '') ?? null;
            },
            openModal() {
                this.open = true;
                this.tab = 'library';
                this.selectedPath = this.uploadedPath ?? @js($currentPath);
                document.body.classList.add('fi-has-open-modal');
            },
            closeModal() {
                this.open = false;
                document.body.classList.remove('fi-has-open-modal');
            },
            choosePath(path) {
                this.selectedPath = path;
            },
            confirmSelection() {
                const path = this.selectedPath || this.uploadedPath;

                if (! path) {
                    return;
                }

                $wire.set(@js($statePath), { [crypto.randomUUID()]: path });
                this.closeModal();
            },
            syncUploadedSelection() {
                if (this.uploadedPath) {
                    this.selectedPath = this.uploadedPath;
                }
            },
        }"
        class="media-picker-field space-y-3"
    >
        <div class="flex flex-wrap items-start gap-4">
            @if ($previewUrl)
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="group relative block shrink-0 overflow-hidden rounded-2xl border border-gray-200 shadow-sm dark:border-gray-700">
                    <img
                        src="{{ $previewUrl }}"
                        alt=""
                        class="h-28 w-28 object-cover transition group-hover:scale-105"
                    />
                    <span class="absolute inset-x-0 bottom-0 bg-black/50 px-2 py-1 text-center text-[10px] text-white">پیش‌نمایش</span>
                </a>
            @else
                <div class="flex h-28 w-28 shrink-0 flex-col items-center justify-center rounded-2xl border border-dashed border-primary-300 bg-primary-50 text-primary-500 dark:border-primary-700 dark:bg-primary-950/30">
                    <x-filament::icon icon="heroicon-o-photo" class="mb-1 h-7 w-7" />
                    <span class="text-[11px]">بدون تصویر</span>
                </div>
            @endif

            <div class="flex min-w-[12rem] flex-1 flex-col gap-2">
                <x-filament::button type="button" icon="heroicon-m-photo" @click="openModal()">
                    انتخاب / آپلود تصویر
                </x-filament::button>

                @if ($currentPath)
                    <x-filament::button
                        type="button"
                        color="danger"
                        outlined
                        icon="heroicon-m-trash"
                        wire:click="$set(@js($statePath), [])"
                    >
                        حذف تصویر
                    </x-filament::button>
                @endif
            </div>
        </div>

        <template x-if="open">
            <div
                class="media-picker-modal"
                @keydown.escape.window="closeModal()"
            >
                <div class="media-picker-modal__backdrop" @click="closeModal()"></div>

                <div class="media-picker-modal__panel" role="dialog" aria-modal="true" aria-labelledby="media-picker-title">
                    <div class="media-picker-modal__header">
                        <div class="flex items-center gap-3">
                            <div class="media-picker-modal__header-icon">
                                <x-filament::icon icon="heroicon-o-folder-open" class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 id="media-picker-title" class="text-lg font-bold">مرکز فایل</h3>
                                <p class="mt-1 text-sm opacity-90">از تصاویر قبلی انتخاب کنید یا فایل جدید بارگذاری کنید.</p>
                            </div>
                        </div>
                        <button type="button" class="media-picker-modal__close" @click="closeModal()">
                            <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="media-picker-modal__tabs">
                        <button
                            type="button"
                            @click="tab = 'library'"
                            :class="tab === 'library' ? 'is-active' : ''"
                            class="media-picker-modal__tab"
                        >
                            <x-filament::icon icon="heroicon-m-photo" class="h-4 w-4" />
                            تصاویر موجود
                        </button>
                        <button
                            type="button"
                            @click="tab = 'upload'"
                            :class="tab === 'upload' ? 'is-active' : ''"
                            class="media-picker-modal__tab"
                        >
                            <x-filament::icon icon="heroicon-m-arrow-up-tray" class="h-4 w-4" />
                            بارگذاری
                        </button>
                    </div>

                    <div class="media-picker-modal__body">
                        <template x-if="tab === 'library'">
                            <div>
                                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <input
                                        type="search"
                                        x-model="search"
                                        placeholder="جستجو در نام فایل..."
                                        class="media-picker-modal__search"
                                    />
                                    <p class="text-xs text-gray-500" x-text="filteredFiles.length + ' فایل'"></p>
                                </div>

                                <div class="media-picker-modal__grid">
                                    <template x-for="file in filteredFiles" :key="file.id">
                                        <button
                                            type="button"
                                            @click="choosePath(file.path)"
                                            class="media-picker-modal__card"
                                            :class="selectedPath === file.path ? 'is-selected' : ''"
                                        >
                                            <div class="media-picker-modal__thumb">
                                                <img :src="file.url" :alt="file.name" loading="lazy" />
                                            </div>
                                            <div class="media-picker-modal__meta">
                                                <span class="media-picker-modal__name" x-text="file.name"></span>
                                                <span class="media-picker-modal__size" x-text="file.size"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>

                                <div x-show="filteredFiles.length === 0" class="media-picker-modal__empty">
                                    <x-filament::icon icon="heroicon-o-photo" class="mb-3 h-12 w-12 text-gray-300" />
                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">تصویری در این پوشه یافت نشد</p>
                                    <p class="mt-2 text-xs text-gray-500">از تب «بارگذاری» فایل جدید اضافه کنید.</p>
                                </div>
                            </div>
                        </template>

                        <template x-if="tab === 'upload'">
                            <div>
                                <div class="media-picker-modal__upload-hint">
                                    پس از اتمام بارگذاری، دکمه «تأیید و استفاده» را بزنید.
                                </div>
                                <div
                                    class="media-picker-modal__upload"
                                    x-on:livewire-upload-finish.window="syncUploadedSelection()"
                                >
                                    @include('filament.forms.components.file-upload-pond')
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="media-picker-modal__footer">
                        <p class="text-xs text-gray-500">
                            <span x-show="selectedPath || uploadedPath">فایل انتخاب‌شده آماده تأیید است.</span>
                            <span x-show="!selectedPath && !uploadedPath">ابتدا یک تصویر انتخاب یا بارگذاری کنید.</span>
                        </p>
                        <div class="flex gap-3">
                            <x-filament::button type="button" color="gray" outlined @click="closeModal()">
                                انصراف
                            </x-filament::button>
                            <x-filament::button
                                type="button"
                                icon="heroicon-m-check"
                                @click="confirmSelection()"
                                x-bind:disabled="!selectedPath && !uploadedPath"
                            >
                                تأیید و استفاده
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-dynamic-component>
