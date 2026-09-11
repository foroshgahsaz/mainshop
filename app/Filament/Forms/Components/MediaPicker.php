<?php

namespace App\Filament\Forms\Components;

use App\Models\MediaFile;
use App\Services\Media\ImageOptimizer;
use App\Services\Media\MediaRegistry;
use App\Support\MediaPath;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaPicker extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrateStateUsing(function (mixed $state): ?string {
            if (is_string($state) && $state !== '') {
                return MediaPath::normalize($state) ?? $state;
            }

            if (! is_array($state)) {
                return null;
            }

            foreach ($state as $value) {
                if ($value instanceof TemporaryUploadedFile) {
                    continue;
                }

                if (is_string($value) && $value !== '') {
                    return MediaPath::normalize($value) ?? $value;
                }
            }

            return null;
        });

        $this->registerActions([
            $this->getMediaCenterAction(),
            $this->getClearImageAction(),
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
                    throw ValidationException::withMessages([
                        'selected_path' => 'یک تصویر از کتابخانه انتخاب کنید یا در تب بارگذاری فایل آپلود کنید.',
                    ]);
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

    public function getClearImageAction(): Action
    {
        return Action::make('clearImage')
            ->label('حذف تصویر')
            ->icon('heroicon-m-trash')
            ->color('gray')
            ->outlined()
            ->visible(fn (): bool => filled($this->getCurrentPath()))
            ->action(fn (): mixed => $this->state([]));
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
        $path = $this->extractPathFromUploadState($data['upload_file'] ?? null);

        if (is_string($path) && $path !== '') {
            return $path;
        }

        $selected = $data['selected_path'] ?? null;

        return is_string($selected) && $selected !== '' ? $selected : null;
    }

    protected function extractPathFromUploadState(mixed $upload): ?string
    {
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
                return $value;
            }
        }

        $path = Arr::first(array_filter($upload, fn ($value) => is_string($value) && $value !== ''));

        return is_string($path) && $path !== '' ? $path : null;
    }

    protected function persistUploadedTempFile(TemporaryUploadedFile $file): string
    {
        $disk = 'public';
        $directory = trim((string) $this->getDirectory(), '/') ?: 'uploads';
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'bin';
        $filename = Str::ulid().'.'.$extension;

        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'upload_file' => 'فایل موقت آپلود منقضی شده. دوباره انتخاب کنید و تا پایان آپلود صبر کنید.',
            ]);
        }

        $path = $file->storeAs($directory, $filename, ['disk' => $disk]);
        $path = app(ImageOptimizer::class)->optimize($disk, $path, $directory);

        app(MediaRegistry::class)->registerFromPath(
            $disk,
            $path,
            $file->getClientOriginalName(),
        );

        return $path;
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

    /** @return Collection<int, object> */
    public function getLibraryFiles(): Collection
    {
        return app(\App\Services\Media\MediaLibrary::class)
            ->filesInDirectory(trim((string) $this->getDirectory(), '/'));
    }
}
