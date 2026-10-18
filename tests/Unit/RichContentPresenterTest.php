<?php

namespace Tests\Unit;

use App\Support\RichContentPresenter;
use PHPUnit\Framework\TestCase;

class RichContentPresenterTest extends TestCase
{
    public function test_moves_video_figures_above_body_html(): void
    {
        $html = '<p>توضیح</p>'
            .'<figure class="rich-content-video" data-rich-content-video="1">'
            .'<video src="https://example.test/v.mp4"></video></figure>';

        $split = RichContentPresenter::splitVideos($html);

        $this->assertStringContainsString('rich-content-video', $split['videos']);
        $this->assertStringContainsString('<p>توضیح</p>', $split['body']);
        $this->assertStringNotContainsString('rich-content-video', $split['body']);
    }
}
