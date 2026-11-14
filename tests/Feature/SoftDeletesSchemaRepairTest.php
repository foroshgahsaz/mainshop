<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\Shop\SoftDeletesSchemaRepair;
use App\Support\ConditionalSoftDeletingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SoftDeletesSchemaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_and_user_use_conditional_soft_deleting_scope(): void
    {
        $this->assertTrue(Order::hasGlobalScope(ConditionalSoftDeletingScope::class));
        $this->assertTrue(User::hasGlobalScope(ConditionalSoftDeletingScope::class));
    }

    public function test_repair_command_is_idempotent_when_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumn('orders', 'deleted_at'));
        $this->assertTrue(Schema::hasColumn('users', 'deleted_at'));

        SoftDeletesSchemaRepair::forgetColumnCache();
        $actions = app(SoftDeletesSchemaRepair::class)->ensure();

        $this->assertSame([], $actions);
        $this->artisan('shop:repair-soft-deletes')->assertSuccessful();
    }

    public function test_has_deleted_at_column_reflects_schema(): void
    {
        SoftDeletesSchemaRepair::forgetColumnCache();
        $this->assertTrue(SoftDeletesSchemaRepair::hasDeletedAtColumn('orders'));
        $this->assertTrue(SoftDeletesSchemaRepair::hasDeletedAtColumn('users'));
    }
}
