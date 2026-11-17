<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Payment\NopayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class NopayRequestStoredOnPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_request_token_stores_request_and_response_on_payment(): void
    {
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'enabled'], ['value' => '1']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'api_base_url'], ['value' => 'https://nopay.test']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'service_username'], ['value' => 'cbazar']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'service_password_encrypted'], ['value' => 'enc-from-support']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'merchant_number'], ['value' => '233333020']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'amount_unit'], ['value' => 'toman']);

        Http::fake([
            'https://nopay.test/*' => Http::response([
                'result' => null,
                'notification' => [
                    'hasErrors' => true,
                    'errors' => [
                        ['message' => 'خطای احراز هویت', 'code' => -7],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create(['phone' => '09121234567']);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => 4000,
            'final_amount' => 4000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'nopay',
            'status' => Order::STATUS_PENDING,
            'stock_reserved' => true,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 4000,
            'gateway' => 'nopay',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'NOPAYREQ1',
        ]);

        try {
            app(NopayGateway::class)->initiate($payment, $order);
            $this->fail('Expected auth failure');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('احراز هویت', $e->getMessage());
        }

        $raw = $payment->fresh()->raw_response;
        $this->assertIsArray($raw);
        $this->assertSame('cbazar', data_get($raw, 'request.serviceUserName'));
        $this->assertSame('enc-from-support', data_get($raw, 'request.servicePassword'));
        $this->assertSame('service_password_encrypted', data_get($raw, 'password_source'));
        $this->assertSame('233333020', data_get($raw, 'request.merchantNumber'));
        $this->assertSame(-7, data_get($raw, 'response.notification.errors.0.code'));
    }

    public function test_cleared_nopay_plain_does_not_aes_from_toopi_when_encrypted_set(): void
    {
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'enabled'], ['value' => '1']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'api_base_url'], ['value' => 'https://nopay.test']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'service_username'], ['value' => 'cbazar']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'service_password'], ['value' => '']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'service_password_encrypted'], ['value' => 'enc-from-support']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'merchant_number'], ['value' => '233333020']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'amount_unit'], ['value' => 'toman']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'public_key'], ['value' => 'WtX3qPVc1lrHX/+ug9iILQ==']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'private_key'], ['value' => 'AAAAAAAAAAAAAAAAAAAAAA==']);
        Setting::query()->updateOrCreate(['group' => 'toopi', 'key' => 'service_password'], ['value' => 'C3456']);

        Http::fake([
            'https://nopay.test/*' => Http::response([
                'result' => null,
                'notification' => [
                    'hasErrors' => true,
                    'errors' => [
                        ['message' => 'خطای احراز هویت', 'code' => -7],
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create(['phone' => '09121234567']);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => 4000,
            'final_amount' => 4000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'nopay',
            'status' => Order::STATUS_PENDING,
            'stock_reserved' => true,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 4000,
            'gateway' => 'nopay',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'NOPAYREQ3',
        ]);

        try {
            app(NopayGateway::class)->initiate($payment, $order);
            $this->fail('Expected auth failure');
        } catch (RuntimeException) {
            // expected
        }

        $raw = $payment->fresh()->raw_response;
        $this->assertSame('enc-from-support', data_get($raw, 'request.servicePassword'));
        $this->assertSame('service_password_encrypted', data_get($raw, 'password_source'));
        $this->assertNotSame('aes_from_service_password', data_get($raw, 'password_source'));
    }

    public function test_request_is_persisted_even_when_http_throws(): void
    {
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'enabled'], ['value' => '1']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'api_base_url'], ['value' => 'https://nopay.test']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'service_username'], ['value' => 'cbazar']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'service_password_encrypted'], ['value' => 'enc-from-support']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'merchant_number'], ['value' => '233333020']);
        Setting::query()->updateOrCreate(['group' => 'nopay', 'key' => 'amount_unit'], ['value' => 'toman']);

        Http::fake([
            'https://nopay.test/*' => Http::response('bad-gateway', 502),
        ]);

        $user = User::factory()->create(['phone' => '09121234567']);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => 4000,
            'final_amount' => 4000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'nopay',
            'status' => Order::STATUS_PENDING,
            'stock_reserved' => true,
        ]);

        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 4000,
            'gateway' => 'nopay',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'NOPAYREQ2',
        ]);

        try {
            app(NopayGateway::class)->initiate($payment, $order);
            $this->fail('Expected HTTP failure');
        } catch (RuntimeException) {
            // expected
        }

        $raw = $payment->fresh()->raw_response;
        $this->assertSame('enc-from-support', data_get($raw, 'request.servicePassword'));
        $this->assertNotEmpty(data_get($raw, 'exception'));
    }
}
