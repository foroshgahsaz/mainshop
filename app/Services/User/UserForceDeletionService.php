<?php

namespace App\Services\User;

use App\Models\Order;
use App\Models\User;
use App\Services\Cart\StockService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** حذف فیزیکی کاربر و داده‌های وابسته — فقط برای مدیر سیستم. */
class UserForceDeletionService
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    public function forceDelete(User $user): void
    {
        if (auth()->id() !== null && (int) auth()->id() === (int) $user->getKey()) {
            throw new RuntimeException('نمی‌توانید حساب ورود فعلی خود را به‌طور کامل حذف کنید.');
        }

        DB::transaction(function () use ($user): void {
            $user->refresh();

            foreach ($user->orders()->withTrashed()->get() as $order) {
                $this->purgeOrder($order);
            }

            $user->payments()->delete();

            $user->cartItems()->delete();
            $user->addresses()->delete();
            $user->couponUsages()->delete();
            $user->wishlists()->delete();
            $user->reviews()->delete();
            $user->productQuestions()->delete();

            if ($user->representativeProfile) {
                $user->representativeProfile()->delete();
            }

            $user->forceDelete();
        });
    }

    protected function purgeOrder(Order $order): void
    {
        if ($order->trashed()) {
            $order->refresh();
        }

        if ($order->stock_reserved) {
            $this->stockService->restoreOrderItems($order);
        }

        $order->payments()->delete();

        $order->items()->delete();
        $order->invoiceLines()->delete();
        $order->notes()->delete();
        $order->reservationRequests()->delete();

        $order->forceDelete();
    }
}