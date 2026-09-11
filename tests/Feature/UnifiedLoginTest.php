<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\LoginModal;
use App\Livewire\Checkout\CheckoutPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_shows_both_auth_tabs(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('ورود با موبایل', false)
            ->assertSee('ورود با نام کاربری', false)
            ->assertSee('دریافت کد تایید', false);
    }

    public function test_login_page_stores_redirect_query_as_intended_url(): void
    {
        $this->get(route('login', ['redirect' => '/checkout']))
            ->assertOk();

        $this->assertSame(url('/checkout'), session('url.intended'));
    }

    public function test_login_modal_open_event_remembers_redirect_url(): void
    {
        Livewire::test(LoginModal::class)
            ->dispatch('open-login-modal', redirect: '/products/demo')
            ->assertSet('activeTab', 'otp');

        $this->assertSame(url('/products/demo'), session('url.intended'));
    }

    public function test_guest_checkout_prompts_for_login_instead_of_redirecting_away(): void
    {
        Livewire::test(CheckoutPage::class)
            ->assertSet('awaitingLogin', true)
            ->assertSee('برای تکمیل خرید ابتدا وارد حساب کاربری شوید')
            ->assertSee('data-open-login', false);

        $this->assertSame(route('checkout'), session('url.intended'));
    }

    public function test_homepage_includes_login_modal_shell_for_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('loginModal', false)
            ->assertSee('data-open-login', false);
    }
}
