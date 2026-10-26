<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Payment\ParsianGateway;
use App\Services\Payment\PecIpgClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ParsianGatewayVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_confirms_successful_parsian_callback(): void
    {
        Setting::query()->create(['group' => 'parsian', 'key' => 'enabled', 'value' => '1']);
        Setting::query()->create(['group' => 'parsian', 'key' => 'login_account', 'value' => 'login-pin']);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => 100_000,
            'final_amount' => 100_000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'parsian',
            'status' => Order::STATUS_PENDING,
            'stock_reserved' => true,
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 100_000,
            'gateway' => 'parsian',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'PARVERIFY01',
            'transaction_id' => 'token-xyz',
            'raw_response' => [
                'callback' => [
                    'Token' => 'token-xyz',
                    'status' => '0',
                    'RRN' => '123456789',
                ],
            ],
        ]);

        $confirmResult = (object) [
            'ConfirmPaymentResult' => (object) [
                'Status' => '0',
                'RRN' => '123456789',
                'CardNumberMasked' => '5022-29**-****-1234',
            ],
        ];

        $pec = Mockery::mock(PecIpgClient::class);
        $pec->shouldReceive('confirmPayment')
            ->once()
            ->with('login-pin', 'token-xyz')
            ->andReturn($confirmResult);

        $this->instance(PecIpgClient::class, $pec);

        $gateway = app(ParsianGateway::class);
        $result = $gateway->verify($payment, 'token-xyz', '0');

        $this->assertTrue($result->successful);
        $this->assertSame('5022-29**-****-1234', $result->cardPan);
        $this->assertSame('123456789', data_get($result->raw, 'rrn'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
