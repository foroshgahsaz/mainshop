<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Settings\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function enableMaintenanceMode(): void
    {
        app(SettingsService::class)->set('site', 'maintenance_mode', true);
    }

    public function test_guest_sees_maintenance_page_when_mode_is_enabled(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertHeader('X-Maintenance-Mode', '1');
        $response->assertSee('سایت در حال بروزرسانی است');
    }

    public function test_admin_can_browse_shop_during_maintenance(): void
    {
        $this->enableMaintenanceMode();

        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('home'));

        $response->assertOk();
    }

    public function test_non_admin_user_sees_maintenance_page(): void
    {
        $this->enableMaintenanceMode();

        $user = User::factory()->create([
            'is_admin' => false,
            'status' => true,
        ]);

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertHeader('X-Maintenance-Mode', '1');
        $response->assertSee('سایت در حال بروزرسانی است');
    }

    public function test_livewire_requests_receive_json_503_during_maintenance(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->post('/livewire/update', [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(503);
    }

    public function test_admin_panel_remains_accessible_during_maintenance(): void
    {
        $this->enableMaintenanceMode();

        $response = $this->get('/admin/login');

        $response->assertOk();
    }
}
