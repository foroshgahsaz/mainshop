<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\PaymentResource;
use App\Models\Order;
use App\Models\User;
use App\Services\Auth\AdminLoginGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_access_admin_login(): void
    {
        $accountant = User::factory()->create([
            'is_accountant' => true,
            'status' => true,
        ]);

        $this->assertTrue(app(AdminLoginGuard::class)->canAccessAdminLogin($accountant->fresh()));
    }

    public function test_accountant_can_view_orders_and_payments_list(): void
    {
        $accountant = User::factory()->create([
            'is_accountant' => true,
            'status' => true,
        ]);

        $this->actingAs($accountant);

        $this->get(OrderResource::getUrl('index'))->assertOk();
        $this->get(PaymentResource::getUrl('index'))->assertOk();
    }

    public function test_accountant_cannot_open_products_list(): void
    {
        $accountant = User::factory()->create([
            'is_accountant' => true,
            'status' => true,
        ]);

        $this->actingAs($accountant);

        $this->get('/admin/products')->assertForbidden();
    }

    public function test_accountant_can_open_order_detail_page(): void
    {
        $customer = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'total_amount' => 100_000,
            'final_amount' => 100_000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'zarinpal',
            'status' => Order::STATUS_PROCESSING,
        ]);

        $accountant = User::factory()->create([
            'is_accountant' => true,
            'status' => true,
        ]);

        $this->actingAs($accountant);

        $this->get(OrderResource::getUrl('view', ['record' => $order]))->assertOk();
    }

    public function test_accountant_can_download_order_excel_export(): void
    {
        $accountant = User::factory()->create([
            'is_accountant' => true,
            'status' => true,
        ]);

        $this->actingAs($accountant);

        $response = $this->get('/admin/orders/export');
        $response->assertOk();
        $this->assertStringContainsString('spreadsheet', (string) $response->headers->get('Content-Type'));
    }
}