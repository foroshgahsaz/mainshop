<?php

namespace Tests\Feature;

use Tests\TestCase;

class PlansDigipayLeasingPageTest extends TestCase
{
    public function test_digipay_leasing_page_is_available(): void
    {
        $this->get('/plans/digipay-leasing/')
            ->assertOk()
            ->assertSee('خرید اقساطی از چینی بازار با دیجی‌پی', false)
            ->assertSee('200', false);
    }

    public function test_digipay_leasing_redirects_without_trailing_slash(): void
    {
        $this->get('/plans/digipay-leasing')
            ->assertRedirect('/plans/digipay-leasing/');
    }
}
