<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\User\UserSoftDeletionService;
use App\Support\UserIdentityUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UserSoftDeletedIdentityReuseTest extends TestCase
{
    use RefreshDatabase;

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
}
