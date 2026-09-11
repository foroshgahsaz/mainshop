<?php

namespace Tests\Unit;

use App\Filament\Forms\Components\MediaPicker;
use Tests\TestCase;

class MediaPickerTest extends TestCase
{
    public function test_it_resolves_selected_library_path_from_modal_data(): void
    {
        $picker = MediaPicker::make('image')->directory('categories');

        $path = $this->invokeResolvePath($picker, [
            'selected_path' => 'categories/shoes.webp',
            'upload_file' => [],
        ]);

        $this->assertSame('categories/shoes.webp', $path);
    }

    public function test_it_resolves_uploaded_path_from_modal_data(): void
    {
        $picker = MediaPicker::make('image')->directory('categories');

        $path = $this->invokeResolvePath($picker, [
            'selected_path' => null,
            'upload_file' => [
                'file-key' => 'categories/new.webp',
            ],
        ]);

        $this->assertSame('categories/new.webp', $path);
    }

    public function test_it_prefers_upload_over_library_selection(): void
    {
        $picker = MediaPicker::make('image')->directory('categories');

        $path = $this->invokeResolvePath($picker, [
            'selected_path' => 'categories/old.webp',
            'upload_file' => [
                'file-key' => 'categories/new.webp',
            ],
        ]);

        $this->assertSame('categories/new.webp', $path);
    }

    /** @param  array<string, mixed>  $data */
    protected function invokeResolvePath(MediaPicker $picker, array $data): ?string
    {
        $method = new \ReflectionMethod($picker, 'resolvePathFromModalData');
        $method->setAccessible(true);

        return $method->invoke($picker, $data);
    }
}
