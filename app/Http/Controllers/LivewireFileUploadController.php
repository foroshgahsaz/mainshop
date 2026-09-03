<?php

namespace App\Http\Controllers;

use App\Support\LivewireUploadFilename;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
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
            'disk' => FileUploadConfiguration::disk(),
            'file_keys' => array_keys(request()->allFiles()),
            'host' => request()->getHost(),
            'scheme' => request()->getScheme(),
        ]);

        try {
            $response = parent::handle();

            Log::info('livewire.upload.response', [
                'paths' => is_array($response) ? count($response['paths'] ?? []) : null,
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
        $files = Arr::wrap($files);

        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules(),
        ])->validate();

        $storePath = trim(FileUploadConfiguration::path(), '/');

        $fileHashPaths = collect($files)->map(function (UploadedFile $file) use ($disk, $storePath) {
            if (! $file->isValid()) {
                Log::error('livewire.upload.invalid_php_upload', [
                    'error' => $file->getErrorMessage(),
                    'error_code' => $file->getError(),
                    'pathname' => $file->getPathname(),
                ]);

                throw ValidationException::withMessages([
                    'files' => 'فایل به سرور نرسید. دوباره انتخاب کنید. اگر تکرار شد، upload_tmp_dir سرور را بررسی کنید.',
                ]);
            }

            $filename = LivewireUploadFilename::generate($file);

            $storedPath = $file->storeAs($storePath, $filename, [
                'disk' => $disk,
            ]);

            if (! is_string($storedPath) || $storedPath === '') {
                Log::error('livewire.upload.store_failed', [
                    'disk' => $disk,
                    'store_path' => $storePath,
                    'filename' => $filename,
                    'pathname' => $file->getPathname(),
                    'size' => $file->getSize(),
                ]);

                throw ValidationException::withMessages([
                    'files' => 'فایل روی دیسک ذخیره نشد. دسترسی پوشه livewire-tmp را بررسی کنید.',
                ]);
            }

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

        return $fileHashPaths->map(function (string $path) use ($storePath) {
            $prefix = $storePath.'/';

            return str_starts_with($path, $prefix)
                ? substr($path, strlen($prefix))
                : $path;
        });
    }
}
