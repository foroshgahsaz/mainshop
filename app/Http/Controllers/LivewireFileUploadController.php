<?php

namespace App\Http\Controllers;

use App\Support\LivewireUploadFilename;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController as BaseFileUploadController;

class LivewireFileUploadController extends BaseFileUploadController
{
    public function handle()
    {
        Log::info('livewire.upload.request', [
            'disk' => request()->input('disk'),
            'file_keys' => array_keys(request()->allFiles()),
            'host' => request()->getHost(),
            'scheme' => request()->getScheme(),
        ]);

        try {
            $response = parent::handle();

            Log::info('livewire.upload.response', [
                'status' => $response->getStatusCode(),
            ]);

            return $response;
        } catch (\Throwable $exception) {
            Log::error('livewire.upload.failed', [
                'message' => $exception->getMessage(),
                'type' => $exception::class,
            ]);

            throw $exception;
        }
    }

    public function validateAndStore($files, $disk)
    {
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules(),
        ])->validate();

        $directory = trim(FileUploadConfiguration::directory(), '/');

        $fileHashPaths = collect($files)->map(function ($file) use ($disk, $directory) {
            $filename = LivewireUploadFilename::generate($file);

            $storedPath = $file->storeAs($directory, $filename, [
                'disk' => $disk,
            ]);

            if (! Storage::disk($disk)->exists($storedPath)) {
                Log::error('livewire.upload.missing_after_store', [
                    'disk' => $disk,
                    'path' => $storedPath,
                    'absolute' => Storage::disk($disk)->path($storedPath),
                ]);

                throw ValidationException::withMessages([
                    'files' => 'فایل آپلود نشد. لطفاً دوباره تلاش کنید.',
                ]);
            }

            Log::info('livewire.upload.stored', [
                'disk' => $disk,
                'path' => $storedPath,
                'absolute' => Storage::disk($disk)->path($storedPath),
                'size' => Storage::disk($disk)->size($storedPath),
            ]);

            return $storedPath;
        });

        return $fileHashPaths->map(function ($path) use ($directory) {
            $prefix = $directory.'/';

            return str_starts_with($path, $prefix)
                ? substr($path, strlen($prefix))
                : $path;
        });
    }
}
