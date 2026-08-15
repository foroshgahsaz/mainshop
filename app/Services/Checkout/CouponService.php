<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use RuntimeException;

class CouponService
{
    public function findValid(string $code, User $user, int $orderTotal): Coupon
    {
        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (! $coupon || ! $coupon->isValidFor($user, $orderTotal)) {
            throw new RuntimeException('کد تخفیف معتبر نیست.');
        }

        return $coupon;
    }

    public function recordUsage(Coupon $coupon, User $user, Order $order): void
    {
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'used_at' => now(),
        ]);
    }
}
