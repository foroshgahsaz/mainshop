<?php

namespace App\Filament\Forms\Components;

use App\Models\MediaFile;
use App\Services\Media\MediaRegistry;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class MediaPicker extends FileUpload
{
    protected string $view = 'filament.forms.components.media-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerActions([
            fn (): Action => $this->getOpenMediaCenterAction(),
        ]);
    }

    public function getOpenMediaCenterAction(): Action
    {
        return Action::make('openMediaCenter')
            ->label('مرکز فایل')
            ->icon('heroicon-m-folder-open')
            ->modalHeading('مرکز فایل')
            ->modalDescription('از تصاویر موجود انتخاب کنید یا فایل جدید بارگذاری کنید.')
            ->modalWidth('7xl')
            ->modalSubmitActionLabel('تأیید و استفاده')
            ->modalCancelActionLabel('انصراف')
            ->closeModalByClickingAway(true)
            ->form(fn (): array => $this->getMediaCenterFormSchema())
            ->action(function (array $data): void {
                $path = $this->resolveSelectedPath($data);

                if (! is_string($path) || $path === '') {
                    return;
                }

                $this->state([(string) Str::uuid() => $path]);
            });
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    protected function getMediaCenterFormSchema(): array
    {
        $files = $this->getLibraryFiles();

        return [
            Tabs::make('media_center')
                ->tabs([
                    Tabs\Tab::make('library')
                        ->label('تصاویر موجود')
                        ->icon('heroicon-m-photo')
                        ->schema([
                            Radio::make('selected_path')
                                ->label($files->isEmpty()
                                    ? 'فایلی روی دیسک نیست. از تب بارگذاری استفاده کنید.'
                                    : 'یک تصویر را انتخاب کنید')
                                ->options($this->libraryRadioOptions($files))
                                ->allowHtml()
                                ->columns(5)
                                ->required(fn (callable $get): bool => blank($get('upload'))),
                        ]),
                    Tabs\Tab::make('upload')
                        ->label('بارگذاری')
                        ->icon('heroicon-m-arrow-up-tray')
                        ->schema([
                            FileUpload::make('upload')
                                ->label('فایل جدید')
                                ->image()
                                ->disk('public')
                                ->directory((string) $this->getDirectory())
                                ->visibility('public')
                                ->maxSize((int) ($this->getMaxSize() ?: 51200))
                                ->helperText('تا پایان نوار پیشرفت صبر کنید، بعد تأیید و استفاده را بزنید.'),
                        ]),
                ]),
        ];
    }

    /** @param  Collection<int, MediaFile>  $files */
    protected function libraryRadioOptions(Collection $files): array
    {
        return $files
            ->mapWithKeys(function (MediaFile $file): array {
                $url = e((string) $file->url());
                $name = e($file->original_name ?: basename($file->path));

                return [
                    $file->path => new HtmlString(
                        '<span class="media-picker-radio-card">'
                        .'<img src="'.$url.'" alt="'.$name.'" />'
                        .'<span>'.$name.'</span>'
                        .'</span>'
                    ),
                ];
            })
            ->all();
    }

    /** @param  array<string, mixed>  $data */
    protected function resolveSelectedPath(array $data): ?string
    {
        $selected = $data['selected_path'] ?? null;

        if (is_string($selected) && $selected !== '') {
            return $selected;
        }

        $upload = $data['upload'] ?? null;

        if (is_string($upload) && $upload !== '') {
            return $upload;
        }

        if (is_array($upload)) {
            $path = collect($upload)->first(fn ($value) => is_string($value) && $value !== '');

            return is_string($path) ? $path : null;
        }

        return null;
    }

    /** @return Collection<int, MediaFile> */
    public function getLibraryFiles(): Collection
    {
        $directory = trim((string) $this->getDirectory(), '/');
        $disk = Storage::disk('public');
        $registry = app(MediaRegistry::class);
        $files = collect();

        MediaFile::query()
            ->latest('id')
            ->limit(200)
            ->get()
            ->each(function (MediaFile $file) use ($files): void {
                if ($file->existsOnDisk()) {
                    $files->put($file->path, $file);
                }
            });

        $folders = array_values(array_unique(array_filter([
            $directory,
            ...array_keys(config('media-library.folders', [])),
        ])));

        foreach ($folders as $folder) {
            if ($folder === '' || ! $disk->exists($folder)) {
                continue;
            }

            foreach ($disk->allFiles($folder) as $path) {
                if ($files->has($path) || ! $this->isImagePath($path)) {
                    continue;
                }

                $files->put($path, $registry->registerFromPath('public', $path));
            }
        }

        return $files
            ->sortByDesc(function (MediaFile $file) use ($directory): int {
                $inFolder = $directory !== '' && str_starts_with($file->path, $directory.'/') ? 1 : 0;

                return ($inFolder * 1_000_000) + (int) ($file->id ?? 0);
            })
            ->take(80)
            ->values();
    }

    protected function isImagePath(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'], true);
    }
}
