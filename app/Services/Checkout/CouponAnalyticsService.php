<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Collection;

class CouponAnalyticsService
{
    /** @return array<string, mixed> */
    public function summary(Coupon $coupon): array
    {
        $usageCount = $coupon->usages()->count();
        $uniqueUsers = (int) $coupon->usages()->distinct('user_id')->count('user_id');

        $ordersQuery = Order::query()->where('coupon_id', $coupon->id);

        return [
            'usage_count' => $usageCount,
            'usage_limit' => $coupon->usage_limit,
            'unique_users' => $uniqueUsers,
            'orders_count' => (int) (clone $ordersQuery)->count(),
            'total_discount' => (int) (clone $ordersQuery)->sum('discount_amount'),
            'total_revenue' => (int) (clone $ordersQuery)->sum('final_amount'),
            'average_order_amount' => (int) round((float) (clone $ordersQuery)->avg('final_amount')),
        ];
    }

    /** @return Collection<int, \App\Models\CouponUsage> */
    public function recentUsages(Coupon $coupon, int $limit = 100): Collection
    {
        return $coupon->usages()
            ->with(['user:id,name,phone', 'order:id,tracking_code,final_amount,discount_amount,status,created_at'])
            ->latest('used_at')
            ->limit($limit)
            ->get();
    }
}
