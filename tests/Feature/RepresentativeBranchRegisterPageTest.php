<?php

namespace Tests\Feature;

use Tests\TestCase;

class RepresentativeBranchRegisterPageTest extends TestCase
{
    public function test_branch_register_page_is_available(): void
    {
        $this->get('/representative/branch/register/')
            ->assertOk()
            ->assertSee('درخواست نمایندگی', false)
            ->assertSee('مناطق فروش', false)
            ->assertSee('دفتر تهران', false)
            ->assertSee('پیش‌نمایش ثبت شد', false);
    }

    public function test_branch_register_redirects_without_trailing_slash(): void
    {
        $this->get('/representative/branch/register')
            ->assertRedirect('/representative/branch/register/');
    }
}
