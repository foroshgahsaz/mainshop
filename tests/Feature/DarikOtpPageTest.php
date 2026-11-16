<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DarikOtpPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_otp_page_uses_account_layout_not_blank_shell(): void
    {
        [$user, $payment] = $this->makePendingDarikPayment();

        $this->actingAs($user)
            ->get(route('payment.darik.otp', $payment->tracking_code))
            ->assertOk()
            ->assertSee('تأیید پرداخت داریک', false)
            ->assertSee('رمز خرید (OTP)', false)
            ->assertSee('سفارش‌ها', false)
            ->assertSee('account-shell', false)
            ->assertSee('darik-otp-card', false)
            ->assertSee('بازگشت به سفارش‌ها', false);
    }

    public function test_confirm_otp_passes_code_to_verify_without_undefined_variable(): void
    {
        [$user, $payment] = $this->makePendingDarikPayment();

        $paymentService = Mockery::mock(PaymentService::class);
        $paymentService->shouldReceive('verify')
            ->once()
            ->withArgs(function (Payment $p, string $otp, string $status) use ($payment): bool {
                return $p->id === $payment->id
                    && $otp === '12345'
                    && $status === 'confirm';
            })
            ->andThrow(new \RuntimeException('رمز خرید نامعتبر است.'));
        $this->app->instance(PaymentService::class, $paymentService);

        $this->actingAs($user)
            ->from(route('payment.darik.otp', $payment->tracking_code))
            ->post(route('payment.darik.confirm', $payment->tracking_code), [
                'otp' => '12345',
            ])
            ->assertRedirect(route('payment.darik.otp', $payment->tracking_code))
            ->assertSessionHas('error', 'رمز خرید نامعتبر است.');
    }

    /**
     * @return array{0: User, 1: Payment}
     */
    protected function makePendingDarikPayment(): array
    {
        $user = User::factory()->create();
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'receiver_name' => 'Test',
            'receiver_phone' => '09120000000',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'تست',
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'tracking_code' => 'ORD'.uniqid(),
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'darik',
            'total_amount' => 4000,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'final_amount' => 4000,
        ]);

        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'gateway' => 'darik',
            'tracking_code' => 'PAY'.strtoupper(substr(uniqid(), -8)),
            'amount' => 4000,
            'status' => Payment::STATUS_PENDING,
            'raw_response' => [
                'darik' => [
                    'transaction_token' => 'txn-token',
                    'reference_code' => 123,
                    'member_code' => '5892201173468905',
                ],
            ],
        ]);

        return [$user, $payment];
    }
}
