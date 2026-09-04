<?php

namespace App\Filament\Forms\Components;

use App\Models\MediaFile;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Collection;

class MediaPicker extends FileUpload
{
    protected string $view = 'filament.forms.components.media-picker';

    /** @return Collection<int, MediaFile> */
    public function getLibraryFiles(): Collection
    {
        $directory = $this->getDirectory();

        return MediaFile::query()
            ->when(filled($directory), function ($query) use ($directory): void {
                $query->where(function ($inner) use ($directory): void {
                    $inner->where('folder', $directory)
                        ->orWhere('path', 'like', $directory.'/%');
                });
            })
            ->latest('id')
            ->limit(72)
            ->get()
            ->filter(fn (MediaFile $file): bool => $file->existsOnDisk())
            ->values();
    }
}
