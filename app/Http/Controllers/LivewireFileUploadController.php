<?php

namespace App\Http\Controllers;

use App\Support\LivewireUploadFilename;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController as BaseFileUploadController;

class LivewireFileUploadController extends BaseFileUploadController
{
    public function validateAndStore($files, $disk)
    {
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules(),
        ])->validate();

        $fileHashPaths = collect($files)->map(function ($file) use ($disk) {
            $filename = LivewireUploadFilename::generate($file);

            $storedPath = $file->storeAs('/'.FileUploadConfiguration::path(), $filename, [
                'disk' => $disk,
            ]);

            if (! Storage::disk($disk)->exists($storedPath)) {
                throw ValidationException::withMessages([
                    'files' => 'فایل آپلود نشد. لطفاً دوباره تلاش کنید.',
                ]);
            }

            return $storedPath;
        });

        return $fileHashPaths->map(function ($path) {
            return str_replace(FileUploadConfiguration::path('/'), '', $path);
        });
    }
}
