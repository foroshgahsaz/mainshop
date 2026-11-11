<?php

namespace Tests\Feature;

use Tests\TestCase;

class HowToBuyPageTest extends TestCase
{
    public function test_how_to_buy_page_is_available(): void
    {
        $this->get('/how-to-buy/')
            ->assertOk()
            ->assertSee('روش‌های خرید اقساطی', false)
            ->assertSee('سقف وام', false)
            ->assertSee('کالاپی', false)
            ->assertSee('الوپی', false)
            ->assertSee('کالانو (باجت)', false)
            ->assertSee('دیجی‌پی', false)
            ->assertSee('نوپی', false)
            ->assertSee('تارا', false)
            ->assertSee('BNPL بانک تجارت', false)
            ->assertSee('گرین‌پی', false);
    }

    public function test_how_to_buy_redirects_without_trailing_slash(): void
    {
        $this->get('/how-to-buy')
            ->assertRedirect('/how-to-buy/');
    }
}
