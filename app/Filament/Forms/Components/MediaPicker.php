<?php

namespace App\Filament\Forms\Components;

use App\Models\MediaFile;
use App\Services\Media\MediaRegistry;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaPicker extends FileUpload
{
    protected string $view = 'filament.forms.components.media-picker';

    /** @return Collection<int, MediaFile> */
    public function getLibraryFiles(): Collection
    {
        $directory = trim((string) $this->getDirectory(), '/');
        $disk = Storage::disk('public');
        $registry = app(MediaRegistry::class);
        $files = collect();

        MediaFile::query()
            ->when(filled($directory), function ($query) use ($directory): void {
                $query->where(function ($inner) use ($directory): void {
                    $inner->where('folder', $directory)
                        ->orWhere('path', 'like', $directory.'/%');
                });
            })
            ->latest('id')
            ->limit(120)
            ->get()
            ->each(function (MediaFile $file) use ($files): void {
                if ($file->existsOnDisk()) {
                    $files->put($file->path, $file);
                }
            });

        if (filled($directory) && $disk->exists($directory)) {
            foreach ($disk->allFiles($directory) as $path) {
                if ($files->has($path) || ! $this->isImagePath($path) || ! $disk->exists($path)) {
                    continue;
                }

                $files->put($path, $registry->registerFromPath('public', $path));
            }
        }

        return $files
            ->sortByDesc(fn (MediaFile $file) => $file->id ?? 0)
            ->take(72)
            ->values();
    }

    protected function isImagePath(string $path): bool
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'], true);
    }
}
