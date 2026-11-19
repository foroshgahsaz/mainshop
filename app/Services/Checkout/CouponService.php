<?php

namespace App\Services\Checkout;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Support\ShopLabels;
use Carbon\Carbon;
use RuntimeException;

class CouponService
{
    public function __construct(
        protected PaymentGatewayCatalog $gateways,
        protected CouponApplicabilityService $applicability,
    ) {}

    public function findValid(string $code, User $user, int $orderTotal, ?string $paymentMethod = null, ?array $lines = null): Coupon
    {
        $result = $this->validate($code, $user, $orderTotal, $paymentMethod, $lines);

        if (! $result['valid']) {
            throw new RuntimeException($result['message'] ?? 'کد تخفیف معتبر نیست.');
        }

        return $result['coupon'];
    }

    /**
     * @param  list<array{product_id: int, quantity: int, price: int, line_total?: int}>|null  $lines
     * @return array{valid: bool, coupon: ?Coupon, message: ?string, eligible_subtotal?: int}
     */
    public function validate(string $code, User $user, int $orderTotal, ?string $paymentMethod = null, ?array $lines = null): array
    {
        $normalized = strtoupper(trim($code));

        if ($normalized === '') {
            return ['valid' => true, 'coupon' => null, 'message' => null];
        }

        $coupon = Coupon::query()->where('code', $normalized)->first();

        if (! $coupon) {
            return [
                'valid' => false,
                'coupon' => null,
                'message' => 'کد تخفیف یافت نشد. املای کد را بررسی کنید.',
            ];
        }

        [$eligibleSubtotal, $amountForRules] = $this->resolveAmounts($coupon, $orderTotal, $lines);

        $scopeMessage = $this->applicability->scopeFailureMessage($coupon, $eligibleSubtotal);
        if ($scopeMessage !== null) {
            return ['valid' => false, 'coupon' => null, 'message' => $scopeMessage];
        }

        $message = $this->failureMessage($coupon, $user, $amountForRules, $paymentMethod);

        if ($message !== null) {
            return ['valid' => false, 'coupon' => null, 'message' => $message];
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'message' => null,
            'eligible_subtotal' => $eligibleSubtotal,
        ];
    }

    /**
     * @param  list<array{product_id: int, quantity: int, price: int, line_total?: int}>|null  $lines
     */
    public function failureMessage(Coupon $coupon, ?User $user, int $orderTotal, ?string $paymentMethod = null, ?array $lines = null): ?string
    {
        if (! $coupon->is_active) {
            return 'این کد تخفیف غیرفعال است.';
        }

        $now = Carbon::now();

        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return 'زمان استفاده از این کد هنوز فرا نرسیده است (شروع: '.$coupon->starts_at->shopJalali().').';
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            return 'مهلت استفاده از این کد تخفیف به پایان رسیده است.';
        }

        [, $amountForRules] = $this->resolveAmounts($coupon, $orderTotal, $lines);

        if ($coupon->minimum_order_amount && $amountForRules < $coupon->minimum_order_amount) {
            $scopeHint = $this->applicability->appliesToAll($coupon)
                ? 'سبد'
                : 'اقلام مشمول این کد';

            return 'حداقل مبلغ '.$scopeHint.' برای این کد '
                .ShopLabels::formatMoney($coupon->minimum_order_amount)
                .' است؛ مبلغ فعلی '
                .ShopLabels::formatMoney($amountForRules)
                .' است.';
        }

        if ($coupon->usage_limit !== null && $coupon->usages()->count() >= $coupon->usage_limit) {
            return 'سقف استفاده از این کد تخفیف تکمیل شده است.';
        }

        if ($user && $coupon->usage_per_user !== null) {
            $userUsageCount = $coupon->usages()->where('user_id', $user->id)->count();
            if ($userUsageCount >= $coupon->usage_per_user) {
                return 'شما قبلاً حداکثر دفعات مجاز ('.$coupon->usage_per_user.') از این کد را استفاده کرده‌اید.';
            }
        }

        if ($paymentMethod !== null && $paymentMethod !== '') {
            $allowed = $coupon->allowedPaymentGateways();
            if ($allowed !== [] && ! in_array($paymentMethod, $allowed, true)) {
                return 'این کد برای درگاه پرداخت انتخاب‌شده معتبر نیست. درگاه‌های مجاز: '
                    .$this->formatGatewayLabels($allowed).'.';
            }
        }

        return null;
    }

    public function isValidFor(Coupon $coupon, ?User $user, int $orderTotal, ?string $paymentMethod = null, ?array $lines = null): bool
    {
        return $this->failureMessage($coupon, $user, $orderTotal, $paymentMethod, $lines) === null;
    }

    /**
     * @param  list<array{product_id: int, quantity: int, price: int, line_total?: int}>|null  $lines
     * @return array{0: int, 1: int} eligible subtotal, amount for min-order rules
     */
    protected function resolveAmounts(Coupon $coupon, int $orderTotal, ?array $lines): array
    {
        if ($this->applicability->appliesToAll($coupon)) {
            $gross = $lines !== null
                ? $this->applicability->grossFromLines($lines)
                : $orderTotal;

            return [$gross, $gross];
        }

        $eligible = $lines !== null
            ? $this->applicability->eligibleSubtotal($coupon, $lines)
            : 0;

        return [$eligible, $eligible];
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

    /** @param  list<string>  $gatewayNames */
    protected function formatGatewayLabels(array $gatewayNames): string
    {
        $labels = [];
        foreach ($gatewayNames as $name) {
            if ($name === 'cod') {
                $labels[] = $this->gateways->codDefinition()['label'];
                continue;
            }
            $labels[] = $this->gateways->definition($name)['label'] ?? $name;
        }

        return implode('، ', $labels);
    }
}
