<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\User\UserSoftDeletionService;
use Illuminate\Validation\ValidationException;
use App\Support\AdminAccess;
use App\Support\UserIdentityUnique;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class UserSoftDeletedIdentityReuseTest extends TestCase
{
    use RefreshDatabase;

    private function resetAccessMemo(): void
    {
        foreach (['memoUser', 'memoIsFullAdmin', 'memoIsSalesManagerOnly', 'memoIsBankdarOnly'] as $p) {
            $r = new \ReflectionProperty(AdminAccess::class, $p);
            $r->setValue(null, null);
        }
        app()->forgetScopedInstances();
    }

    private function actAs(User $user): void
    {
        $this->resetAccessMemo();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_unique_rule_ignores_soft_deleted_users(): void
    {
        $trashed = User::factory()->create([
            'phone' => '09335588712',
            'email' => '5560727067@chinibazar.ir',
            'national_code' => '5560727067',
        ]);
        $trashed->delete();

        $validator = Validator::make(
            [
                'phone' => '09335588712',
                'email' => '5560727067@chinibazar.ir',
                'national_code' => '5560727067',
            ],
            [
                'phone' => [UserIdentityUnique::rule('phone')],
                'email' => [UserIdentityUnique::rule('email')],
                'national_code' => [UserIdentityUnique::rule('national_code')],
            ]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_unique_rule_still_blocks_active_users(): void
    {
        User::factory()->create([
            'phone' => '09335588712',
            'national_code' => '5560727067',
        ]);

        $validator = Validator::make(
            ['phone' => '09335588712'],
            ['phone' => [UserIdentityUnique::rule('phone')]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_purge_trashed_identity_conflicts_allows_recreate(): void
    {
        $trashed = User::factory()->create([
            'phone' => '09335588712',
            'email' => '5560727067@chinibazar.ir',
            'national_code' => '5560727067',
            'password' => bcrypt('secret'),
        ]);
        $trashedId = (int) $trashed->id;
        $trashed->delete();

        $this->assertSoftDeleted('users', ['id' => $trashedId]);

        $purged = app(UserSoftDeletionService::class)->purgeTrashedIdentityConflicts(
            phone: '09335588712',
            email: '5560727067@chinibazar.ir',
            nationalCode: '5560727067',
        );

        $this->assertSame(1, $purged);
        $this->assertDatabaseMissing('users', ['id' => $trashedId]);

        $fresh = User::factory()->create([
            'phone' => '09335588712',
            'email' => '5560727067@chinibazar.ir',
            'national_code' => '5560727067',
            'password' => bcrypt('/*5560727067/*'),
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $fresh->id,
            'phone' => '09335588712',
            'national_code' => '5560727067',
            'deleted_at' => null,
        ]);
    }

    public function test_full_admin_eloquent_query_includes_soft_deleted_users(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => true]);
        $trashed = User::factory()->create(['phone' => '09121112233', 'status' => true]);
        $trashed->delete();

        $this->actAs($admin);

        $ids = UserResource::getEloquentQuery()->pluck('id')->all();
        $this->assertContains((int) $trashed->id, array_map('intval', $ids));
    }

    public function test_bankdar_eloquent_query_hides_soft_deleted_users(): void
    {
        $bankdar = User::factory()->create([
            'is_bankdar' => true,
            'is_admin' => false,
            'status' => true,
            'phone' => '09120001111',
        ]);
        $trashed = User::factory()->create([
            'phone' => '09120002222',
            'status' => true,
            'is_representative' => true,
        ]);
        $trashed->delete();

        $this->actAs($bankdar);

        $ids = UserResource::getEloquentQuery()->pluck('id')->all();
        $this->assertNotContains((int) $trashed->id, array_map('intval', $ids));
    }

    public function test_admin_list_shows_soft_deleted_user_with_default_trashed_filter(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'status' => true, 'phone' => '09120003333']);
        $trashed = User::factory()->create([
            'name' => 'کاربر حذف‌شده تست',
            'phone' => '09120004444',
            'status' => true,
        ]);
        $trashed->delete();

        $this->actAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$trashed]);
    }

    public function test_user_soft_delete_allowed_when_only_trashed_orders(): void
    {
        $user = User::factory()->create();
        $order = $this->createMinimalOrderForUser($user);
        $order->delete();

        $outcome = app(UserSoftDeletionService::class)->delete($user->fresh());

        $this->assertSame('deleted', $outcome);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_national_code_save_rejects_when_active_user_has_derived_email(): void
    {
        $other = User::factory()->create([
            'national_code' => '0072138343',
            'email' => '0072138343@chinibazar.ir',
        ]);

        $user = User::factory()->create([
            'national_code' => null,
            'email' => 'standalone@example.com',
        ]);

        $user->national_code = '0072138343';

        try {
            $user->save();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('national_code', $exception->errors());
            $this->assertStringContainsString((string) $other->id, $exception->errors()['national_code'][0]);
        }
    }

    public function test_national_code_save_purges_trashed_duplicate_then_succeeds(): void
    {
        $trashed = User::factory()->create([
            'national_code' => '0072138343',
            'email' => '0072138343@chinibazar.ir',
        ]);
        $trashedId = (int) $trashed->id;
        $trashed->delete();

        $user = User::factory()->create([
            'national_code' => null,
            'email' => 'other@example.com',
        ]);

        $user->national_code = '0072138343';
        $user->save();

        $this->assertSame('0072138343@chinibazar.ir', $user->fresh()->email);
        $this->assertDatabaseMissing('users', ['id' => $trashedId]);
    }

    protected function createMinimalOrderForUser(User $user): Order
    {
        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'receiver_name' => 'علی',
            'receiver_phone' => '09121112233',
            'province' => 'تهران',
            'city' => 'تهران',
            'address' => 'خیابان تست',
            'postal_code' => '1234567890',
        ]);

        return Order::query()->create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'total_amount' => 1000,
            'final_amount' => 1000,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => 'online',
            'status' => 'pending',
        ]);
    }
}
