<?php

namespace Tests\Feature;

use App\Services\Representative\RepresentativeDashboardService;
use App\Support\ShopDate;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

class RepresentativeDashboardDatesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_jalali_month_start_is_first_day_of_current_shamsi_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-12 09:00:00', 'UTC'));

        $start = app(RepresentativeDashboardService::class)->jalaliMonthStart();

        $this->assertInstanceOf(Carbon::class, $start);

        $jalali = Jalalian::fromCarbon($start->copy()->timezone(ShopDate::TIMEZONE));
        $this->assertSame(1405, $jalali->getYear());
        $this->assertSame(7, $jalali->getMonth());
        $this->assertSame(1, $jalali->getDay());
        $this->assertSame('00:00', $start->copy()->timezone(ShopDate::TIMEZONE)->format('H:i'));
        $this->assertTrue($start->lessThanOrEqualTo(now()));
    }

    public function test_jalali_month_label_does_not_throw(): void
    {
        $label = app(RepresentativeDashboardService::class)->jalaliMonthLabel();

        $this->assertNotSame('', $label);
    }
}
