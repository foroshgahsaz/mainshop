<?php

namespace Tests\Unit;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use Filament\Forms\Form;
use Mockery;
use PHPUnit\Framework\TestCase;

class UserEditAccountSaveTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_access_fields_resolve_staff_kind_from_preserved_flags_when_tab_fields_missing(): void
    {
        $form = Mockery::mock(Form::class);
        $form->shouldReceive('getRawState')->andReturn([]);

        $data = CreateUser::applyAccessFieldsFromForm($form, [
            'phone' => '09223334455',
            'is_admin' => false,
            'is_author' => false,
            'is_representative' => true,
            'is_sales_manager' => false,
        ]);

        $this->assertTrue($data['is_representative']);
        $this->assertFalse($data['is_admin']);
    }

    public function test_strip_virtual_keys_keeps_phone_for_account_tab_payload(): void
    {
        $data = CreateUser::stripVirtualAccessFormKeys([
            'phone' => '09120001122',
            'email' => 'user@example.com',
            'status' => true,
            'user_kind' => 'customer',
            'is_representative' => true,
        ]);

        $this->assertSame('09120001122', $data['phone']);
        $this->assertTrue($data['is_representative']);
        $this->assertArrayNotHasKey('user_kind', $data);
    }
}
