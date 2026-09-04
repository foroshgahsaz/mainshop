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
                this.open = false;
            },
            syncUploadedSelection() {
                if (this.uploadedPath) {
                    this.selectedPath = this.uploadedPath;
                }
            },
        }"
        x-effect="document.body.classList.toggle('fi-has-open-modal', open)"
        class="space-y-3"
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

        <template x-teleport="body">
            <div
                x-show="open"
                x-cloak
                class="media-picker-modal fixed inset-0 z-[99999] flex items-center justify-center p-4 sm:p-6"
                @keydown.escape.window="open = false"
            >
                <div
                    class="absolute inset-0 bg-gray-950/85 backdrop-blur-sm"
                    @click="open = false"
                ></div>

                <div class="relative z-10 flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 ring-white/10 dark:bg-gray-900">
                    <div class="bg-gradient-to-l from-primary-600 to-primary-500 px-6 py-5 text-white">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 backdrop-blur">
                                    <x-filament::icon icon="heroicon-o-folder-open" class="h-6 w-6" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold">مرکز فایل</h3>
                                    <p class="mt-1 text-sm text-white/80">از تصاویر قبلی انتخاب کنید یا فایل جدید بارگذاری کنید.</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="rounded-xl bg-white/10 p-2 text-white transition hover:bg-white/20"
                                @click="open = false"
                            >
                                <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 bg-gray-50 px-6 dark:border-gray-700 dark:bg-gray-950/40">
                        <nav class="-mb-px flex gap-8">
                            <button
                                type="button"
                                @click="tab = 'library'"
                                :class="tab === 'library'
                                    ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                                class="flex items-center gap-2 border-b-2 px-1 py-4 text-sm font-semibold"
                            >
                                <x-filament::icon icon="heroicon-m-photo" class="h-4 w-4" />
                                تصاویر موجود
                            </button>
                            <button
                                type="button"
                                @click="tab = 'upload'; $nextTick(() => syncUploadedSelection())"
                                :class="tab === 'upload'
                                    ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                                class="flex items-center gap-2 border-b-2 px-1 py-4 text-sm font-semibold"
                            >
                                <x-filament::icon icon="heroicon-m-arrow-up-tray" class="h-4 w-4" />
                                بارگذاری
                            </button>
                        </nav>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-white p-6 dark:bg-gray-900">
                        <div x-show="tab === 'library'">
                            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <input
                                    type="search"
                                    x-model="search"
                                    placeholder="جستجو در نام فایل..."
                                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white sm:max-w-md"
                                />
                                <p class="text-xs text-gray-500" x-text="filteredFiles.length + ' فایل'"></p>
                            </div>

                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                                <template x-for="file in filteredFiles" :key="file.id">
                                    <button
                                        type="button"
                                        @click="choosePath(file.path)"
                                        class="group relative overflow-hidden rounded-2xl border text-start transition"
                                        :class="selectedPath === file.path
                                            ? 'border-primary-500 ring-2 ring-primary-500/30 shadow-lg'
                                            : 'border-gray-200 hover:border-primary-300 hover:shadow-md dark:border-gray-700'"
                                    >
                                        <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-gray-800">
                                            <img
                                                :src="file.url"
                                                :alt="file.name"
                                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                                loading="lazy"
                                            />
                                        </div>
                                        <div class="space-y-1 px-3 py-3">
                                            <span class="block truncate text-xs font-medium text-gray-800 dark:text-gray-200" x-text="file.name"></span>
                                            <span class="block text-[10px] text-gray-400" x-text="file.size"></span>
                                        </div>
                                        <div
                                            x-show="selectedPath === file.path"
                                            class="absolute left-3 top-3 flex h-7 w-7 items-center justify-center rounded-full bg-primary-600 text-white shadow"
                                        >
                                            <x-filament::icon icon="heroicon-m-check" class="h-4 w-4" />
                                        </div>
                                    </button>
                                </template>
                            </div>

                            <div
                                x-show="filteredFiles.length === 0"
                                class="flex flex-col items-center justify-center rounded-3xl border border-dashed border-gray-300 bg-gray-50 px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-950/40"
                            >
                                <x-filament::icon icon="heroicon-o-photo" class="mb-3 h-12 w-12 text-gray-300" />
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">تصویری در این پوشه یافت نشد</p>
                                <p class="mt-2 text-xs text-gray-500">از تب «بارگذاری» فایل جدید اضافه کنید.</p>
                            </div>
                        </div>

                        <div x-show="tab === 'upload'" x-cloak>
                            <div class="mb-4 rounded-2xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm text-primary-700 dark:border-primary-900 dark:bg-primary-950/40 dark:text-primary-300">
                                پس از اتمام بارگذاری، دکمه «تأیید و استفاده» را بزنید.
                            </div>
                            <div
                                x-on:livewire-upload-finish.window="syncUploadedSelection()"
                                x-effect="if (tab === 'upload') { syncUploadedSelection(); }"
                            >
                                @include('filament.forms.components.file-upload-pond')
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-950/50 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-gray-500">
                            <span x-show="selectedPath || uploadedPath">فایل انتخاب‌شده آماده تأیید است.</span>
                            <span x-show="!selectedPath && !uploadedPath">ابتدا یک تصویر انتخاب یا بارگذاری کنید.</span>
                        </p>
                        <div class="flex gap-3">
                            <x-filament::button type="button" color="gray" outlined @click="open = false">
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
