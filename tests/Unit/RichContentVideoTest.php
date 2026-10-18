<?php

namespace Tests\Unit;

use App\Filament\Support\RichContentVideo;
use PHPUnit\Framework\TestCase;

class RichContentVideoTest extends TestCase
{
    public function test_appends_video_figure_to_html(): void
    {
        $result = RichContentVideo::appendToHtml('<p>متن</p>', 'https://example.test/a.mp4');

        $this->assertStringContainsString('<p>متن</p>', $result);
        $this->assertStringContainsString('rich-content-video', $result);
        $this->assertStringContainsString('https://example.test/a.mp4', $result);
    }
}
