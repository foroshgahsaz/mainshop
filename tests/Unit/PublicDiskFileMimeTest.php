<?php

namespace Tests\Unit;

use App\Support\PublicDiskFileMime;
use PHPUnit\Framework\TestCase;

class PublicDiskFileMimeTest extends TestCase
{
    public function test_css_min_files_use_text_css(): void
    {
        $this->assertSame('text/css; charset=UTF-8', PublicDiskFileMime::forPath('shop-min/css/tailwind.min.css'));
    }

    public function test_js_min_files_use_javascript_mime(): void
    {
        $this->assertSame('application/javascript; charset=UTF-8', PublicDiskFileMime::forPath('shop-min/js/main.min.js'));
    }
}
