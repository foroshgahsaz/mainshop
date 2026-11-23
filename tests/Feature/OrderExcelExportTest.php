<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderExcelExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_orders_excel(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('filament.admin.orders.export'));

        $response->assertOk();
        $response->assertHeader(
            'content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
        $this->assertStringStartsWith('PK', (string) $response->streamedContent());
    }

    public function test_guest_cannot_download_orders_excel(): void
    {
        $this->get(route('filament.admin.orders.export'))
            ->assertRedirect();
    }
}