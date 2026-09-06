<?php

namespace App\Filament\Forms\Components;

use App\Models\MediaFile;
use App\Services\Media\MediaRegistry;
use App\Support\ShopMedia;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaPicker extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerActions([
            $this->getMediaCenterAction(),
        ]);
    }

    public function getMediaCenterAction(): Action
    {
        return Action::make('openMediaCenter')
            ->label('انتخاب / تغییر تصویر')
            ->icon('heroicon-m-photo')
            ->color('primary')
            ->modalHeading('مرکز رسانه')
            ->modalDescription('از فایل‌های قبلی انتخاب کنید یا فایل جدید بارگذاری کنید.')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('تأیید و استفاده')
            ->modalCancelActionLabel('انصراف')
            ->fillForm(fn (): array => $this->getMediaCenterFormDefaults())
            ->form(fn (): array => $this->getMediaCenterFormSchema())
            ->action(function (array $data, MediaRegistry $registry): void {
                $path = $this->resolvePathFromModalData($data);

                if (! is_string($path) || $path === '') {
                    return;
                }

                $this->state([(string) Str::uuid() => $path]);

                $registry->registerFromPath('public', $path);
                $registry->updateSeo(
                    'public',
                    $path,
                    filled($data['alt_text'] ?? null) ? (string) $data['alt_text'] : null,
                    filled($data['title'] ?? null) ? (string) $data['title'] : null,
                );
            });
    }

    /** @return array<string, mixed> */
    protected function getMediaCenterFormDefaults(): array
    {
        $path = $this->getCurrentPath();
        $media = $this->findMediaFile($path);

        return [
            'selected_path' => $path,
            'upload_file' => [],
            'alt_text' => $media?->alt_text,
            'title' => $media?->title,
        ];
    }

    /** @return array<int, mixed> */
    protected function getMediaCenterFormSchema(): array
    {
        $directory = trim((string) $this->getDirectory(), '/');

        return [
            Tabs::make('media_center_tabs')
                ->tabs([
                    Tabs\Tab::make('library')
                        ->label('مرکز فایل')
                        ->icon('heroicon-m-photo')
                        ->schema([
                            ViewField::make('selected_path')
                                ->view('filament.forms.components.media-library-grid')
                                ->viewData(fn (): array => [
                                    'files' => $this->getLibraryFiles(),
                                    'directoryLabel' => config('media-library.folders.'.$directory, $directory),
                                ]),
                        ]),
                    Tabs\Tab::make('upload')
                        ->label('بارگذاری')
                        ->icon('heroicon-m-arrow-up-tray')
                        ->schema([
                            FileUpload::make('upload_file')
                                ->label('فایل جدید')
                                ->image()
                                ->disk('public')
                                ->directory($directory !== '' ? $directory : 'uploads')
                                ->visibility('public')
                                ->maxSize(51200)
                                ->maxFiles(1)
                                ->helperText('پس از انتخاب، فایل آپلود می‌شود. سپس فیلدهای سئو را تکمیل و تأیید کنید.'),
                        ]),
                ])
                ->contained(false)
                ->persistTabInQueryString(false),
            Section::make('سئو تصویر')
                ->description('این اطلاعات برای موتورهای جستجو و دسترس‌پذیری تصویر استفاده می‌شود.')
                ->icon('heroicon-m-magnifying-glass')
                ->schema([
                    TextInput::make('title')
                        ->label('عنوان تصویر (Title)')
                        ->maxLength(255)
                        ->placeholder('مثلاً: پیراهن مردانه کلاسیک آبی'),
                    Textarea::make('alt_text')
                        ->label('متن جایگزین (Alt)')
                        ->rows(2)
                        ->maxLength(500)
                        ->placeholder('توضیح کوتاه تصویر برای موتور جستجو و نابینایان'),
                ])
                ->columns(1)
                ->compact(),
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function resolvePathFromModalData(array $data): ?string
    {
        $upload = $data['upload_file'] ?? null;

        if (is_array($upload) && $upload !== []) {
            $path = Arr::first(array_filter($upload, fn ($value) => is_string($value) && $value !== ''));

            if (is_string($path) && $path !== '') {
                return $path;
            }
        }

        $selected = $data['selected_path'] ?? null;

        return is_string($selected) && $selected !== '' ? $selected : null;
    }

    public function getCurrentPath(): ?string
    {
        $state = $this->getState();

        if (! is_array($state)) {
            return is_string($state) && $state !== '' ? $state : null;
        }

        foreach ($state as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function findMediaFile(?string $path): ?MediaFile
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return MediaFile::query()
            ->where('disk', 'public')
            ->where('path', $path)
            ->first();
    }

    /** @return Collection<int, object{path: string, url: ?string, name: string, alt_text: ?string, title: ?string}> */
    public function getLibraryFiles(): Collection
    {
        $directory = trim((string) $this->getDirectory(), '/');
        $disk = Storage::disk('public');
        $files = collect();

        try {
            MediaFile::query()
                ->when($directory !== '', fn ($query) => $query->where('folder', $directory))
                ->latest('id')
                ->limit(120)
                ->get()
                ->each(function (MediaFile $file) use ($files): void {
                    if (! $this->safeExists($file->disk, $file->path)) {
                        return;
                    }

                    $files->put($file->path, (object) [
                        'path' => $file->path,
                        'url' => $file->url(),
                        'name' => $file->original_name ?: basename($file->path),
                        'alt_text' => $file->alt_text,
                        'title' => $file->title,
                    ]);
                });
        } catch (\Throwable) {
            // Library listing must never break the edit form.
        }

        if ($directory !== '' && $disk->exists($directory)) {
            try {
                foreach ($disk->allFiles($directory) as $path) {
                    if ($files->has($path) || ! $this->isImagePath($path)) {
                        continue;
                    }

                    $files->put($path, (object) [
                        'path' => $path,
                        'url' => ShopMedia::url($path),
                        'name' => basename($path),
                        'alt_text' => null,
                        'title' => null,
                    ]);
                }
            } catch (\Throwable) {
                // Ignore unreadable folders.
            }
        }

        return $files
            ->sortByDesc(function (object $file): int {
                try {
                    return (int) @filemtime(Storage::disk('public')->path($file->path));
                } catch (\Throwable) {
                    return 0;
                }
            })
            ->take(60)
            ->values();
    }

    protected function safeExists(string $disk, string $path): bool
    {
        try {
            return Storage::disk($disk)->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isImagePath(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'], true);
    }
}
