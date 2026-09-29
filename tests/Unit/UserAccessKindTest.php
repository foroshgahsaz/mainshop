<?php

namespace Tests\Unit;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use PHPUnit\Framework\TestCase;

class UserAccessKindTest extends TestCase
{
    public function test_staff_representative_flags_are_preserved_after_normalize(): void
    {
        $data = CreateUser::normalizeUserKind([
            'user_kind' => 'staff',
            'is_admin' => false,
            'is_author' => false,
            'is_representative' => true,
            'name' => 'نماینده تست',
        ]);

        $this->assertTrue($data['is_representative']);
        $this->assertFalse($data['is_admin']);
        $this->assertArrayNotHasKey('user_kind', $data);
    }

    public function test_customer_kind_clears_staff_flags(): void
    {
        $data = CreateUser::normalizeUserKind([
            'user_kind' => 'customer',
            'is_representative' => true,
        ]);

        $this->assertFalse($data['is_representative']);
        $this->assertFalse($data['is_admin']);
    }

    public function test_resolve_user_kind_from_flags(): void
    {
        $this->assertSame('staff', CreateUser::resolveUserKindFromFlags([
            'is_representative' => true,
        ]));
        $this->assertSame('customer', CreateUser::resolveUserKindFromFlags([
            'is_admin' => false,
            'is_representative' => false,
        ]));
    }
}
