<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Order\OrderActivityLogger;
use App\Services\Payment\BajetPayGateway;
use App\Services\Payment\PaymentActivityLogger;
use App\Services\Payment\PaymentAuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class BajetPayGatewayTokenRetryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_order_retries_once_when_access_token_expired(): void
    {
        Cache::flush();

        $config = [
            'enabled' => true,
            'base_url' => 'https://bajet-api.test',
            'username' => 'merchant',
            'password' => 'secret',
            'terminal_id' => 'T-1',
            'amount_unit' => 'toman',
            'callback_url' => '/payment/callback/bajet',
            'return_url_base' => 'https://shop.test',
            'default_product_type' => 2,
            'default_brand' => 'general',
        ];

        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('bajet')->andReturn($config);
        $this->app->instance(SettingsService::class, $settings);

        Cache::put('bajet:access_token:'.sha1('https://bajet-api.test|merchant|T-1'), 'stale-token', 600);

        Http::fake([
            'bajet-api.test/api/v1/jetpay/order' => Http::sequence()
                ->push([
                    'success' => false,
                    'status' => 400,
                    'result' => [
                        'error' => [
                            'fa' => 'تاریخ درخواست گذشته است',
                            'en' => 'REQUEST_EXPIRED',
                            'code' => 6660031,
                        ],
                    ],
                ], 400)
                ->push([
                    'success' => true,
                    'result' => [
                        'referenceId' => 'ref-new',
                        'referUrl' => 'https://portal.test/fa/ep/invoice?requestId=ref-new',
                    ],
                ], 200),
            'bajet-api.test/api/v1/jetpay/token' => Http::response([
                'success' => true,
                'result' => [
                    'token' => 'fresh-token',
                    'expiresIn' => 3600,
                ],
            ], 200),
        ]);

        $user = new User(['phone' => '09121234567']);
        $user->id = 1;

        $order = new Order([
            'final_amount' => 100_000,
            'payment_method' => 'bajet',
        ]);
        $order->id = 10;
        $order->setRelation('user', $user);
        $order->setRelation('items', new Collection);

        $payment = Mockery::mock(Payment::class)->makePartial();
        $payment->id = 99;
        $payment->amount = 100_000;
        $payment->gateway = 'bajet';
        $payment->tracking_code = 'TESTPAY12345';
        $payment->status = Payment::STATUS_PENDING;
        $payment->shouldReceive('update')->andReturnTrue();
        $payment->shouldReceive('fresh')->andReturnSelf();

        $audit = Mockery::mock(PaymentAuditLogger::class);
        $audit->shouldReceive('step')->andReturnNull();
        $audit->shouldReceive('failure')->never();

        $gateway = new BajetPayGateway(
            Mockery::mock(PaymentActivityLogger::class)->shouldIgnoreMissing(),
            $audit,
            Mockery::mock(OrderActivityLogger::class)->shouldIgnoreMissing(),
            $settings,
        );

        $redirect = $gateway->initiate($payment, $order);

        $this->assertSame(
            'https://portal.test/fa/ep/invoice?requestId=ref-new',
            $redirect
        );

        Http::assertSentCount(3);
    }
}
