<?php

namespace Tests\Unit;

use App\Filament\Forms\Components\MediaPicker;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteNameHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_name_reads_from_settings(): void
    {
        app(SettingsService::class)->set('site', 'name', 'فروشگاه تست');

        $this->assertSame('فروشگاه تست', site_name());
    }

    public function test_media_picker_extends_file_upload(): void
    {
        $picker = MediaPicker::make('image')->directory('products');

        $this->assertInstanceOf(MediaPicker::class, $picker);
        $this->assertSame('products', $picker->getDirectory());
    }
}
