<?php

namespace Tests\Unit;

use App\Models\User;
use App\Support\UserNationalCodeCredentials;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNationalCodeCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_and_password_pattern_from_national_code(): void
    {
        $this->assertSame('4391881276@chinibazar.ir', UserNationalCodeCredentials::emailForNationalCode('4391881276'));
        $this->assertSame('/*4391881276/*', UserNationalCodeCredentials::passwordPlainForNationalCode('4391881276'));
    }

    public function test_user_save_sets_email_and_password_when_national_code_set(): void
    {
        $user = User::factory()->create([
            'national_code' => null,
            'email' => null,
        ]);

        $user->national_code = '0013542419';
        $user->save();

        $user = $user->fresh();
        $this->assertSame('0013542419@chinibazar.ir', $user->email);
        $this->assertTrue(password_verify('/*0013542419/*', (string) $user->password));
    }
}
