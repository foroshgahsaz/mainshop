<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Representative\RepresentativeCustomerService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerTransferOtpUnitTest extends TestCase
{
    public function test_assert_transferable_rejects_own_customer(): void
    {
        $rep = User::make(['is_representative' => true, 'is_admin' => false]);
        $rep->id = 3;

        $customer = User::make([
            'is_representative' => false,
            'is_admin' => false,
            'is_sales_manager' => false,
            'is_bankdar' => false,
            'is_author' => false,
            'created_by_representative_id' => 3,
            'phone' => '09120000000',
        ]);
        $customer->id = 40;

        $service = app(RepresentativeCustomerService::class);

        $this->expectException(ValidationException::class);
        $service->sendTransferOtp($rep, $customer);
    }

    public function test_assert_transferable_rejects_staff_user(): void
    {
        $rep = User::make(['is_representative' => true, 'is_admin' => false]);
        $rep->id = 3;

        $staff = User::make([
            'is_representative' => false,
            'is_admin' => false,
            'is_sales_manager' => true,
            'is_bankdar' => false,
            'is_author' => false,
            'created_by_representative_id' => 9,
            'phone' => '09120000001',
        ]);
        $staff->id = 41;

        $this->expectException(ValidationException::class);
        app(RepresentativeCustomerService::class)->sendTransferOtp($rep, $staff);
    }
}
