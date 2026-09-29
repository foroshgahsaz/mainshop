<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Services\Representative\RepresentativeDraftOrderService;
use PHPUnit\Framework\TestCase;

class RepresentativeDraftOrderServiceTest extends TestCase
{
    public function test_draft_status_constant_exists(): void
    {
        $this->assertSame('draft', Order::STATUS_DRAFT);
    }

    public function test_service_class_is_instantiable(): void
    {
        $this->assertInstanceOf(RepresentativeDraftOrderService::class, new RepresentativeDraftOrderService);
    }
}
