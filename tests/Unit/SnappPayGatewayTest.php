<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentActivityLogger;
use App\Services\Payment\PaymentAuditLogger;
use App\Services\Payment\SnappPayGateway;
use App\Services\Order\OrderActivityLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SnappPayGatewayTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_initiate_returns_payment_page_url(): void
    {
        Http::fake([
            'https://api.snapppay.ir/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'test-bearer',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.ir/api/online/payment/v1/token' => Http::response([
                'successful' => true,
                'response' => [
                    'paymentToken' => 'ptok_1',
                    'paymentPageUrl' => 'https://pay.snapppay.ir/start',
                ],
            ], 200),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('snapppay')->andReturn([
            'enabled' => true,
            'base_url' => 'https://api.snapppay.ir',
            'client_id' => 'cid',
            'client_secret' => 'csec',
            'client_username' => 'user',
            'client_password' => 'pass',
            'amount_unit' => 'toman',
            'callback_url' => '/payment/callback/snapppay',
        ]);

        $user = new User(['phone' => '09121234567']);
        $order = new Order([
            'id' => 10,
            'tracking_code' => 'ORD-SNAP',
            'final_amount' => 1_000_000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
        ]);
        $order->setRelation('user', $user);
        $order->setRelation('items', collect());

        $payment = Payment::make([
            'tracking_code' => 'PAY-SNAP',
            'amount' => 1_000_000,
            'status' => Payment::STATUS_PENDING,
            'gateway' => 'snapppay',
        ]);
        $payment->id = 1;

        $gateway = new SnappPayGateway(
            Mockery::mock(PaymentActivityLogger::class)->shouldIgnoreMissing(),
            Mockery::mock(PaymentAuditLogger::class)->shouldIgnoreMissing(),
            Mockery::mock(OrderActivityLogger::class)->shouldIgnoreMissing(),
            $settings,
        );

        $url = $gateway->initiate($payment, $order);

        $this->assertSame('https://pay.snapppay.ir/start', $url);
    }

    public function test_verify_calls_settle_after_ok_state(): void
    {
        Http::fake([
            'https://api.snapppay.ir/api/online/v1/oauth/token' => Http::response([
                'access_token' => 'test-bearer',
                'expires_in' => 3600,
            ], 200),
            'https://api.snapppay.ir/api/online/payment/v1/verify' => Http::response([
                'successful' => true,
                'response' => ['status' => 'VERIFY'],
            ], 200),
            'https://api.snapppay.ir/api/online/payment/v1/settle' => Http::response([
                'successful' => true,
                'response' => ['status' => 'SETTLE'],
            ], 200),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('snapppay')->andReturn([
            'enabled' => true,
            'base_url' => 'https://api.snapppay.ir',
            'client_id' => 'cid',
            'client_secret' => 'csec',
            'client_username' => 'user',
            'client_password' => 'pass',
            'amount_unit' => 'toman',
            'callback_url' => '/payment/callback/snapppay',
        ]);

        $payment = Payment::make([
            'amount' => 500_000,
            'transaction_id' => 'ptok_1',
            'gateway' => 'snapppay',
        ]);

        $gateway = new SnappPayGateway(
            Mockery::mock(PaymentActivityLogger::class)->shouldIgnoreMissing(),
            Mockery::mock(PaymentAuditLogger::class)->shouldIgnoreMissing(),
            Mockery::mock(OrderActivityLogger::class)->shouldIgnoreMissing(),
            $settings,
        );

        $result = $gateway->verify($payment, 'ptok_1', 'OK');

        $this->assertTrue($result->successful);
        $this->assertSame('ptok_1', $result->cardPan);
    }
}