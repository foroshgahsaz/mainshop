<?php

namespace App\Filament\Support;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FileUploadSanitizer
{
    public static function sanitize(Component $livewire, Form $form, ?Model $record = null): void
    {
        foreach ($form->getFlatFields(withHidden: true) as $field) {
            if (! $field instanceof BaseFileUpload) {
                continue;
            }

            $statePath = $field->getStatePath();

            if ($statePath === null) {
                continue;
            }

            $state = data_get($livewire, $statePath);

            if (! is_array($state)) {
                continue;
            }

            $fallback = $record?->getAttribute($field->getName());

            data_set(
                $livewire,
                $statePath,
                self::sanitizeState($state, is_string($fallback) ? $fallback : null),
            );
        }
    }

    /**
     * @param  array<string, TemporaryUploadedFile|string>  $state
     * @return array<string, TemporaryUploadedFile|string>
     */
    public static function sanitizeState(array $state, ?string $fallbackPath = null): array
    {
        $cleaned = collect($state)->filter(function (TemporaryUploadedFile|string $file): bool {
            if ($file instanceof TemporaryUploadedFile) {
                return $file->isValid();
            }

            return filled($file);
        });

        if ($cleaned->isNotEmpty()) {
            return $cleaned->all();
        }

        if (filled($fallbackPath)) {
            return [(string) Str::uuid() => $fallbackPath];
        }

        return [];
    }
}
