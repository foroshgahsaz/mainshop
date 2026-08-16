<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\ShopLoginGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShopAuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_login_to_shop(): void
    {
        $admin = User::factory()->admin()->create();

        try {
            app(ShopLoginGuard::class)->assertAllowed($admin, 'phone');
            $this->fail('Admin shop login should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('phone', $e->errors());
            $this->assertStringContainsString('/admin', $e->errors()['phone'][0]);
        }
    }

    public function test_inactive_customer_cannot_login_to_shop(): void
    {
        $user = User::factory()->create(['status' => false]);

        $this->expectException(ValidationException::class);

        app(ShopLoginGuard::class)->assertAllowed($user, 'otp');
    }

    public function test_customer_can_login_to_shop(): void
    {
        $user = User::factory()->create();

        app(ShopLoginGuard::class)->assertAllowed($user, 'phone');

        $this->assertTrue($user->status);
        $this->assertFalse($user->is_admin);
    }

    public function test_otp_is_not_stored_when_admin_is_rejected_before_send(): void
    {
        $admin = User::factory()->admin()->create([
            'phone' => '09120000000',
        ]);

        try {
            app(ShopLoginGuard::class)->assertAllowed($admin, 'phone');
        } catch (ValidationException) {
            // expected
        }

        $this->assertFalse(Cache::has('otp:phone:'.$admin->phone));
        $this->assertNull(Cache::get('otp:phone:'.$admin->phone));
    }

    public function test_otp_service_still_issues_codes_for_customers(): void
    {
        $otp = app(OtpService::class);
        $otp->send('09123334444');

        $this->assertTrue(Cache::has('otp:phone:09123334444'));
    }
}
