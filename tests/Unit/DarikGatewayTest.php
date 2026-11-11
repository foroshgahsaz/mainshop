<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Order\OrderActivityLogger;
use App\Services\Payment\DarikApiClient;
use App\Services\Payment\DarikGateway;
use App\Services\Payment\PaymentActivityLogger;
use App\Services\Payment\PaymentAuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class DarikGatewayTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_initiate_registers_order_sends_otp_and_returns_otp_route(): void
    {
        Http::fake([
            'https://darik.test/api/v1/Login' => Http::response([
                'Status' => 'Success',
                'token' => 'login-token',
                'expiry' => now()->addHour()->format('Y-m-d H:i'),
            ]),
            'https://darik.test/api/v1/Order' => Http::response([
                'Status' => 'Success',
                'ReferenceCode' => 107652,
                'TransactionToken' => 'txn-token',
            ]),
            'https://darik.test/api/v1/SendOTP' => Http::response([
                'Status' => 'Success',
                'message' => 'پیامک ارسال شد',
            ]),
        ]);

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('darik')->andReturn([
            'enabled' => true,
            'api_key' => 'key',
            'username' => 'u',
            'password' => 'p',
            'merchant_code' => '102',
            'merchant_group_id' => 2,
            'province_id' => 8,
            'amount_unit' => 'toman',
            'base_url' => 'https://darik.test',
        ]);
        $this->app->instance(SettingsService::class, $settings);

        $user = new User(['darik_member_code' => '5892251560796087']);
        $user->id = 1;

        $order = new Order([
            'final_amount' => 115_000,
            'payment_method' => 'darik',
        ]);
        $order->id = 10;
        $order->setRelation('user', $user);
        $order->setRelation('items', new Collection);

        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->id = 99;
        $payment->amount = 115_000;
        $payment->gateway = 'darik';
        $payment->tracking_code = 'TESTDARIK001';
        $payment->status = Payment::STATUS_PENDING;
        $payment->raw_response = null;
        $payment->shouldReceive('update')->andReturnTrue();
        $payment->shouldReceive('fresh')->andReturnSelf();

        $audit = Mockery::mock(PaymentAuditLogger::class);
        $audit->shouldReceive('step')->andReturnNull();
        $audit->shouldReceive('failure')->never();

        $gateway = new DarikGateway(
            new DarikApiClient,
            Mockery::mock(PaymentActivityLogger::class)->shouldIgnoreMissing(),
            $audit,
            Mockery::mock(OrderActivityLogger::class)->shouldIgnoreMissing(),
            $settings,
        );

        $url = $gateway->initiate($payment, $order);

        $this->assertStringContainsString('/payment/darik/TESTDARIK001', $url);
    }
}
