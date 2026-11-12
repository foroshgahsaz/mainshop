<?php

namespace Tests\Feature;

use App\Filament\Resources\OtpMessageResource;
use App\Filament\Resources\OtpMessageResource\Pages\ListOtpMessages;
use App\Models\OtpMessage;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\AdminAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OtpMessageAdminTest extends TestCase
{
    use RefreshDatabase;

    private function resetAccessMemo(): void
    {
        foreach (['memoUser', 'memoIsFullAdmin', 'memoIsSalesManagerOnly'] as $property) {
            $reflection = new \ReflectionProperty(AdminAccess::class, $property);
            $reflection->setValue(null, null);
        }
        app()->forgetScopedInstances();
    }

    private function actAs(User $user): void
    {
        $this->resetAccessMemo();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_send_otp_stores_phone_code_name_and_sent_at(): void
    {
        $user = User::factory()->create([
            'name' => 'علی رضایی',
            'phone' => '09121112222',
        ]);

        app(OtpService::class)->send($user->phone);

        $row = OtpMessage::query()->first();

        $this->assertNotNull($row);
        $this->assertSame('09121112222', $row->phone);
        $this->assertSame($user->id, $row->user_id);
        $this->assertSame('علی رضایی', $row->user_name);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $row->code);
        $this->assertNotNull($row->sent_at);
        $this->assertSame($row->code, cache()->get('otp:phone:'.$user->phone));
    }

    public function test_send_otp_for_unknown_phone_stores_row_without_user(): void
    {
        app(OtpService::class)->send('09120000000');

        $row = OtpMessage::query()->first();

        $this->assertNotNull($row);
        $this->assertSame('09120000000', $row->phone);
        $this->assertNull($row->user_id);
        $this->assertNull($row->user_name);
        $this->assertSame('—', $row->displayName());
    }

    public function test_admin_can_see_sent_otp_list(): void
    {
        $admin = User::factory()->admin()->create(['phone' => '09123330001']);
        $customer = User::factory()->create([
            'name' => 'مریم احمدی',
            'phone' => '09124440002',
        ]);

        app(OtpService::class)->send($customer->phone);
        $row = OtpMessage::query()->firstOrFail();

        $this->actAs($admin);

        $this->assertTrue(OtpMessageResource::canAccess());

        Livewire::test(ListOtpMessages::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$row])
            ->assertSee('مریم احمدی')
            ->assertSee('09124440002')
            ->assertSee($row->code);
    }

    public function test_sales_manager_cannot_access_otp_messages(): void
    {
        $manager = User::factory()->salesManager()->create(['phone' => '09125550003']);
        $customer = User::factory()->create([
            'name' => 'حسن کریمی',
            'phone' => '09126660004',
        ]);

        app(OtpService::class)->send($customer->phone);

        $this->actAs($manager);

        $this->assertFalse(OtpMessageResource::canAccess());
        $this->assertNotContains(
            'filament.admin.resources.otp-messages.*',
            AdminAccess::salesManagerAllowedRoutePatterns()
        );

        $this->get(OtpMessageResource::getUrl('index'))->assertForbidden();
    }
}
