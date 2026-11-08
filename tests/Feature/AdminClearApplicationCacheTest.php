<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminClearApplicationCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_clear_application_cache(): void
    {
        $response = $this->post(route('filament.admin.tools.clear-application-cache'));

        $response->assertRedirect();
    }

    public function test_admin_can_clear_application_cache(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'status' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('filament.admin.tools.clear-application-cache'));

        $response->assertRedirect();
    }

    public function test_sales_manager_cannot_clear_application_cache(): void
    {
        $manager = User::factory()->create([
            'is_admin' => false,
            'is_sales_manager' => true,
            'status' => true,
        ]);

        $response = $this->actingAs($manager)->post(route('filament.admin.tools.clear-application-cache'));

        $response->assertForbidden();
    }
}
