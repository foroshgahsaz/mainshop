<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlackFridayPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_blackfriday_page_renders_with_cta_and_products_anchor(): void
    {
        $response = $this->get('/blackfriday/');

        $response->assertOk();
        $response->assertSee('مشاهده تخفیف', false);
        $response->assertSee('id="products"', false);
        $response->assertSee('blackfriday-cta', false);
        $response->assertSee('توی تاریکی غرق شو', false);
        $response->assertDontSee('توی تاریکی نئون غرق شو', false);
    }
}