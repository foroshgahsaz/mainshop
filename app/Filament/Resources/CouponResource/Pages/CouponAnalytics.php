<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Models\Coupon;
use App\Services\Checkout\CouponAnalyticsService;
use Filament\Resources\Pages\Page;

class CouponAnalytics extends Page
{
    protected static string $resource = CouponResource::class;

    protected static string $view = 'filament.resources.coupon-resource.pages.coupon-analytics';

    protected static ?string $title = 'آنالیز کد تخفیف';

    public Coupon $record;

    /** @var array<string, mixed> */
    public array $summary = [];

    public function mount(int|string $record, CouponAnalyticsService $analytics): void
    {
        $this->record = Coupon::query()->findOrFail($record);
        $this->summary = $analytics->summary($this->record);
    }

    public function getTitle(): string
    {
        return 'آنالیز: '.$this->record->code;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
