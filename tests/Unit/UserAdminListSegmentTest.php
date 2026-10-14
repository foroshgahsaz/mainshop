<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserAdminListSegmentTest extends TestCase
{
    public function test_admin_list_segment_priority(): void
    {
        $admin = new User(['is_admin' => true, 'is_sales_manager' => true, 'is_representative' => true]);
        $this->assertSame(User::ADMIN_LIST_SEGMENT_SYSTEM_ADMIN, $admin->adminListSegment());
        $this->assertSame('مدیر سیستم', $admin->adminListSegmentLabel());

        $manager = new User(['is_sales_manager' => true, 'is_representative' => true]);
        $this->assertSame(User::ADMIN_LIST_SEGMENT_SALES_MANAGER, $manager->adminListSegment());

        $rep = new User(['is_representative' => true]);
        $this->assertSame(User::ADMIN_LIST_SEGMENT_REPRESENTATIVE, $rep->adminListSegment());

        $author = new User(['is_author' => true]);
        $this->assertSame(User::ADMIN_LIST_SEGMENT_AUTHOR, $author->adminListSegment());

        $customer = new User;
        $this->assertSame(User::ADMIN_LIST_SEGMENT_CUSTOMER, $customer->adminListSegment());
    }
}
