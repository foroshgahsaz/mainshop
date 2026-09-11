<?php

namespace Tests\Unit;

use App\Support\ShopFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShopFormatterImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_image_uses_storage_url_when_file_is_missing(): void
    {
        Storage::fake('public');

        $url = ShopFormatter::categoryImage('categories/shoes.webp');

        $this->assertStringContainsString('categories/shoes.webp', $url);
        $this->assertStringNotContainsString('code.svg', $url);
    }

    public function test_category_image_falls_back_when_path_is_empty(): void
    {
        $url = ShopFormatter::categoryImage(null);

        $this->assertStringContainsString('shop/images/categories/code.svg', $url);
    }
}
