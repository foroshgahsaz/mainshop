@php
    $statePath = $getStatePath();
    $state = $getState();
    $currentPath = collect(is_array($state) ? $state : [])
        ->first(fn ($value) => is_string($value) && $value !== '');
    $previewUrl = $currentPath ? \App\Support\ShopMedia::url($currentPath) : null;
    $libraryFiles = $field->getLibraryFiles()->map(fn ($file) => [
        'id' => $file->id,
        'path' => $file->path,
        'url' => $file->url(),
        'name' => $file->original_name ?: basename($file->path),
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
            files: @js($libraryFiles),
            selectPath(path) {
                $wire.set(@js($statePath), { [crypto.randomUUID()]: path });
                this.open = false;
            },
            filteredFiles() {
                if (! this.search) {
                    return this.files;
                }

                const query = this.search.toLowerCase();

                return this.files.filter((file) =>
                    file.name.toLowerCase().includes(query) ||
                    file.path.toLowerCase().includes(query)
                );
            },
        }"
        class="space-y-3"
    >
        <div class="flex flex-wrap items-start gap-3">
            @if ($previewUrl)
                <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="block shrink-0">
                    <img
                        src="{{ $previewUrl }}"
                        alt=""
                        class="h-24 w-24 rounded-xl border border-gray-200 object-cover dark:border-gray-700"
                    />
                </a>
            @else
                <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 text-xs text-gray-400 dark:border-gray-600 dark:bg-gray-900">
                    بدون تصویر
                </div>
            @endif

            <div class="flex flex-wrap gap-2">
                <x-filament::button type="button" size="sm" @click="open = true; tab = 'library'">
                    انتخاب / آپلود تصویر
                </x-filament::button>

                @if ($currentPath)
                    <x-filament::button
                        type="button"
                        size="sm"
                        color="danger"
                        outlined
                        wire:click="$set(@js($statePath), [])"
                    >
                        حذف
                    </x-filament::button>
                @endif
            </div>
        </div>

        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[120] flex items-center justify-center p-4"
            @keydown.escape.window="open = false"
        >
            <div class="absolute inset-0 bg-gray-950/60" @click="open = false"></div>

            <div class="relative z-10 flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">رسانه</h3>
                        <p class="mt-1 text-xs text-gray-500">از تصاویر موجود انتخاب کنید یا فایل جدید بارگذاری کنید.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800"
                        @click="open = false"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="border-b border-gray-200 px-5 dark:border-gray-700">
                    <nav class="-mb-px flex gap-6">
                        <button
                            type="button"
                            @click="tab = 'library'"
                            :class="tab === 'library'
                                ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                            class="border-b-2 px-1 py-3 text-sm font-medium"
                        >
                            تصاویر موجود
                        </button>
                        <button
                            type="button"
                            @click="tab = 'upload'"
                            :class="tab === 'upload'
                                ? 'border-primary-600 text-primary-600 dark:border-primary-400 dark:text-primary-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                            class="border-b-2 px-1 py-3 text-sm font-medium"
                        >
                            بارگذاری
                        </button>
                    </nav>
                </div>

                <div class="flex-1 overflow-y-auto p-5">
                    <div x-show="tab === 'library'">
                        <input
                            type="search"
                            x-model="search"
                            placeholder="جستجو در کتابخانه..."
                            class="mb-4 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                        />

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            <template x-for="file in filteredFiles()" :key="file.id">
                                <button
                                    type="button"
                                    @click="selectPath(file.path)"
                                    class="overflow-hidden rounded-xl border border-gray-200 text-start transition hover:border-primary-500 hover:shadow-sm dark:border-gray-700"
                                >
                                    <img
                                        :src="file.url"
                                        :alt="file.name"
                                        class="aspect-square w-full object-cover"
                                        loading="lazy"
                                    />
                                    <span class="block truncate px-2 py-2 text-[11px] text-gray-500" x-text="file.name"></span>
                                </button>
                            </template>
                        </div>

                        <p
                            x-show="filteredFiles().length === 0"
                            class="py-10 text-center text-sm text-gray-500"
                        >
                            تصویری در این پوشه یافت نشد. از تب «بارگذاری» فایل جدید اضافه کنید.
                        </p>
                    </div>

                    <div x-show="tab === 'upload'" x-cloak>
                        @include('filament.forms.components.file-upload-pond')
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
