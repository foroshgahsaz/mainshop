<?php

namespace App\Filament\Forms\Components;

use App\Models\MediaFile;
use App\Services\Media\MediaRegistry;
use App\Services\Media\UploadedPublicImageStorage;
use App\Support\MediaPath;
use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaPicker extends Field
{
    protected string $view = 'filament.forms.components.media-picker';

    protected string | Closure $directory = 'uploads';

    protected int | Closure $maxSize = 51200;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (MediaPicker $component, mixed $state): void {
            $path = $component->extractPath($state);

            if ($state === $path) {
                return;
            }

            $component->state($path);
        });

        $this->dehydrateStateUsing(function (mixed $state): ?string {
            return $this->extractPath($state);
        });

        // Closures: cacheActions() runs per field instance (repeater rows) when state path exists.
        $this->registerActions([
            fn (): Action => $this->getMediaCenterAction(),
            fn (): Action => $this->getClearImageAction(),
        ]);
    }

    public function directory(string | Closure $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function maxSize(int | Closure $size): static
    {
        $this->maxSize = $size;

        return $this;
    }

    public function getDirectory(): string
    {
        return trim((string) $this->evaluate($this->directory), '/');
    }

    public function getMaxSize(): int
    {
        return (int) $this->evaluate($this->maxSize);
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
                $data = $this->mergeMountedModalFormData($data);
                $path = $this->resolvePathFromModalData($data);

                if (! is_string($path) || $path === '') {
                    $message = 'یک تصویر از کتابخانه انتخاب کنید یا در تب بارگذاری تا ۱۰۰٪ آپلود کنید، سپس تأیید بزنید.';

                    Notification::make()
                        ->title('تصویری انتخاب نشد')
                        ->body($message)
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'upload_file' => $message,
                    ]);
                }

                $this->applySelectedPath($path);

                $registry->registerFromPath('public', $path);
                $registry->updateSeo(
                    'public',
                    $path,
                    filled($data['alt_text'] ?? null) ? (string) $data['alt_text'] : null,
                    filled($data['title'] ?? null) ? (string) $data['title'] : null,
                );

                Notification::make()
                    ->title('تصویر انتخاب شد')
                    ->body('برای ذخیره روی رکورد، دکمه ذخیره فرم را بزنید.')
                    ->success()
                    ->send();
            });
    }

    public function getClearImageAction(): Action
    {
        return Action::make('clearImage')
            ->label('حذف تصویر')
            ->icon('heroicon-m-trash')
            ->color('gray')
            ->outlined()
            ->visible(fn (MediaPicker $component): bool => filled($component->getCurrentPath()))
            ->action(function (): void {
                $this->applySelectedPath(null);

                Notification::make()
                    ->title('تصویر حذف شد')
                    ->body('برای قطعی شدن، فرم را ذخیره کنید.')
                    ->success()
                    ->send();
            });
    }

    public function applySelectedPath(?string $path): void
    {
        $normalized = is_string($path) && $path !== ''
            ? (MediaPath::normalize($path) ?? $path)
            : null;

        $livewire = $this->getLivewire();
        data_set($livewire, $this->getStatePath(), $normalized);

        $this->state($normalized);
        $this->callAfterStateUpdated();
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
        $directory = $this->getDirectory();
        $maxSize = $this->getMaxSize();
        $tempDisk = (string) config('livewire.temporary_file_upload.disk', 'livewire-tmp');
        $tempDirectory = trim((string) config('livewire.temporary_file_upload.directory', 'livewire-tmp'), '/');

        return [
            Hidden::make('selected_path')
                ->dehydratedWhenHidden(),
            Tabs::make('media_center_tabs')
                ->tabs([
                    Tabs\Tab::make('library')
                        ->label('مرکز فایل')
                        ->icon('heroicon-m-photo')
                        ->schema([
                            ViewField::make('library_browser')
                                ->hiddenLabel()
                                ->dehydrated(false)
                                ->view('filament.forms.components.media-library-grid')
                                ->viewData(fn (): array => [
                                    'directory' => $directory,
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
                                ->storeFiles(false)
                                ->disk($tempDisk)
                                ->directory($tempDirectory !== '' ? $tempDirectory : 'livewire-tmp')
                                ->visibility('private')
                                ->maxSize($maxSize)
                                ->maxFiles(1)
                                ->dehydratedWhenHidden()
                                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, FileUpload $component): string {
                                    if (! $file->isValid()) {
                                        throw ValidationException::withMessages([
                                            $component->getName() => 'فایل موقت آپلود منقضی شده. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
                                        ]);
                                    }

                                    return $file->serializeForLivewireResponse();
                                })
                                ->helperText('پس از انتخاب فایل تا پایان آپلود (۱۰۰٪) صبر کنید، سپس «تأیید و استفاده» بزنید. تصویر فعلی تا ذخیرهٔ فرم اصلی دست‌نخورده می‌ماند.'),
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
        $upload = $data['upload_file'] ?? $this->findNestedFormValue($data, 'upload_file');
        $path = $this->extractPathFromUploadState($upload);

        if (is_string($path) && $path !== '') {
            return $path;
        }

        $selected = $data['selected_path'] ?? $this->findNestedFormValue($data, 'selected_path');

        return $this->extractPath($selected);
    }

    /** @param  array<string, mixed>  $data */
    protected function mergeMountedModalFormData(array $data): array
    {
        $livewire = $this->getLivewire();

        if (! is_array($livewire->mountedFormComponentActionsData ?? null)) {
            return $data;
        }

        $index = array_key_last($livewire->mountedFormComponentActionsData);

        if ($index === null) {
            return $data;
        }

        $mounted = $livewire->mountedFormComponentActionsData[$index] ?? [];

        if (! is_array($mounted)) {
            return $data;
        }

        $merged = array_replace_recursive($data, $mounted);

        $mountedUpload = $this->findNestedFormValue($mounted, 'upload_file');
        $dataUpload = $this->findNestedFormValue($merged, 'upload_file');

        if ($this->uploadStateIsEmpty($dataUpload) && ! $this->uploadStateIsEmpty($mountedUpload)) {
            $merged['upload_file'] = $mountedUpload;
        }

        return $merged;
    }

    protected function uploadStateIsEmpty(mixed $upload): bool
    {
        if ($upload === null) {
            return true;
        }

        if ($upload === []) {
            return true;
        }

        if (is_string($upload) && trim($upload) === '') {
            return true;
        }

        if (is_array($upload)) {
            foreach ($upload as $value) {
                if ($value instanceof TemporaryUploadedFile) {
                    return false;
                }

                if (is_string($value) && trim($value) !== '') {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return mixed
     */
    protected function findNestedFormValue(array $data, string $key): mixed
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        foreach ($data as $value) {
            if (! is_array($value)) {
                continue;
            }

            $found = $this->findNestedFormValue($value, $key);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    protected function extractPathFromUploadState(mixed $upload): ?string
    {
        if (is_string($upload) && $upload !== '') {
            return $this->resolveUploadedReference($upload);
        }

        if (! is_array($upload) || $upload === []) {
            return null;
        }

        foreach ($upload as $value) {
            if ($value instanceof TemporaryUploadedFile) {
                if (! $value->isValid()) {
                    continue;
                }

                return $this->persistUploadedTempFile($value);
            }

            if (is_string($value) && $value !== '') {
                $resolved = $this->resolveUploadedReference($value);

                if (is_string($resolved) && $resolved !== '') {
                    return $resolved;
                }
            }
        }

        $path = Arr::first(array_filter($upload, fn ($value) => is_string($value) && $value !== ''));

        return is_string($path) && $path !== '' ? $this->resolveUploadedReference($path) : null;
    }

    protected function resolveUploadedReference(string $reference): ?string
    {
        $reference = trim($reference);

        if ($reference === '') {
            return null;
        }

        if (str_starts_with($reference, 'livewire-files:')) {
            $payload = substr($reference, strlen('livewire-files:'));

            try {
                $signedPaths = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $signedPaths = [];
            }

            foreach (is_array($signedPaths) ? $signedPaths : [] as $signedPath) {
                if (! is_string($signedPath) || $signedPath === '') {
                    continue;
                }

                $resolved = $this->resolveUploadedReference($signedPath);

                if (is_string($resolved) && $resolved !== '') {
                    return $resolved;
                }
            }

            return null;
        }

        $normalized = MediaPath::normalize($reference) ?? ltrim(str_replace('\\', '/', $reference), '/');

        if (Storage::disk('public')->exists($normalized)) {
            return $normalized;
        }

        if (TemporaryUploadedFile::canUnserialize($reference)) {
            $file = TemporaryUploadedFile::unserializeFromLivewireRequest($reference);

            if ($file instanceof TemporaryUploadedFile && $file->isValid()) {
                return $this->persistUploadedTempFile($file);
            }
        }

        if (str_contains($reference, ':')) {
            $extracted = TemporaryUploadedFile::extractPathFromSignedPath($reference);

            if (is_string($extracted) && $extracted !== '') {
                $file = TemporaryUploadedFile::createFromLivewire($extracted);

                if ($file->isValid()) {
                    return $this->persistUploadedTempFile($file);
                }
            }
        }

        $tempDirectory = trim(FileUploadConfiguration::directory(), '/');

        foreach (array_unique([
            $normalized,
            $reference,
            basename($normalized),
            $tempDirectory.'/'.$normalized,
            $tempDirectory.'/'.basename($normalized),
        ]) as $candidate) {
            $candidate = ltrim(str_replace('\\', '/', $candidate), '/');

            if ($candidate === '') {
                continue;
            }

            $file = TemporaryUploadedFile::createFromLivewire($candidate);

            if ($file->isValid()) {
                return $this->persistUploadedTempFile($file);
            }
        }

        return null;
    }

    protected function persistUploadedTempFile(TemporaryUploadedFile $file): string
    {
        $directory = $this->getDirectory() !== '' ? $this->getDirectory() : 'uploads';

        return app(UploadedPublicImageStorage::class)->store($file, $directory);
    }

    public function getCurrentPath(): ?string
    {
        return $this->extractPath($this->getState());
    }

    public function extractPath(mixed $state): ?string
    {
        if (is_string($state) && $state !== '') {
            return MediaPath::normalize($state) ?? $state;
        }

        if (! is_array($state)) {
            return null;
        }

        foreach ($state as $value) {
            if (is_string($value) && $value !== '') {
                return MediaPath::normalize($value) ?? $value;
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

    /** @return Collection<int, object> */
    public function getLibraryFiles(): Collection
    {
        return app(\App\Services\Media\MediaLibrary::class)
            ->filesInDirectory($this->getDirectory());
    }
}
