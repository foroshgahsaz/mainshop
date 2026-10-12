<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Representative\RepresentativeDashboardService;
use PHPUnit\Framework\TestCase;

class RepresentativeDashboardServiceTest extends TestCase
{
    public function test_active_order_statuses_include_proforma_and_draft(): void
    {
        $statuses = RepresentativeDashboardService::activeOrderStatuses();

        $this->assertContains(Order::STATUS_DRAFT, $statuses);
        $this->assertContains(Order::STATUS_PROFORMA, $statuses);
        $this->assertContains(Order::STATUS_PENDING, $statuses);
        $this->assertContains(Order::STATUS_PROCESSING, $statuses);
    }

    public function test_service_is_instantiable(): void
    {
        $this->assertInstanceOf(RepresentativeDashboardService::class, new RepresentativeDashboardService);
    }
}
