<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\User\UserDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_hard_delete_removes_user_without_orders(): void
    {
        $user = User::factory()->create();

        $outcome = app(UserDeletionService::class)->delete($user);

        $this->assertSame('deleted', $outcome);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}