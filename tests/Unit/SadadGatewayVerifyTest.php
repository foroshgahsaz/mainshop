<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\Payment\SadadGateway;
use App\Services\Payment\SadadIpgClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SadadGatewayVerifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_confirms_successful_sadad_callback(): void
    {
        Setting::query()->create(['group' => 'sadad', 'key' => 'enabled', 'value' => '1']);
        Setting::query()->create(['group' => 'sadad', 'key' => 'terminal_key', 'value' => base64_encode(str_repeat('k', 24))]);

        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => 100_000,
            'final_amount' => 100_000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'sadad',
            'status' => Order::STATUS_PENDING,
            'stock_reserved' => true,
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'amount' => 100_000,
            'gateway' => 'sadad',
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => 'SADVERIFY01',
            'transaction_id' => 'sadad-token-1',
            'raw_response' => [
                'callback' => [
                    'token' => 'sadad-token-1',
                    'ResCode' => '0',
                    'OrderId' => (string) $order->id,
                ],
            ],
        ]);

        $client = Mockery::mock(SadadIpgClient::class);
        $client->shouldReceive('verify')
            ->once()
            ->andReturn([
                'ResCode' => 0,
                'RetrivalRefNo' => '99887766',
                'SystemTraceNo' => '123456',
            ]);

        $this->instance(SadadIpgClient::class, $client);

        $gateway = app(SadadGateway::class);
        $result = $gateway->verify($payment, 'sadad-token-1', '0');

        $this->assertTrue($result->successful);
        $this->assertSame('99887766', data_get($result->raw, 'reference'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
