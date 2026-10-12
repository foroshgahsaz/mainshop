<?php

namespace App\Services\Representative;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\ShopDate;
use App\Support\ShopLabels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Morilog\Jalali\Jalalian;

class RepresentativeDashboardService
{
    /** @return list<string> */
    public static function activeOrderStatuses(): array
    {
        return [
            Order::STATUS_DRAFT,
            Order::STATUS_PROFORMA,
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
        ];
    }

    public function currentOrdersQuery(int $representativeId): Builder
    {
        return Order::query()
            ->where('representative_id', $representativeId)
            ->whereIn('status', self::activeOrderStatuses());
    }

    public function jalaliMonthStart(): Carbon
    {
        $j = Jalalian::now(ShopDate::TIMEZONE);

        return (new Jalalian($j->getYear(), $j->getMonth(), 1, 0, 0, 0))
            ->toCarbon()
            ->timezone(ShopDate::TIMEZONE);
    }

    public function jalaliMonthLabel(): string
    {
        return Jalalian::now(ShopDate::TIMEZONE)->format('F Y');
    }

    /**
     * @return array{
     *     month_label: string,
     *     month_sales: int,
     *     today_sales: int,
     *     active_orders_count: int,
     *     proforma_unpaid_count: int,
     *     expiring_reservations_count: int,
     *     customers_count: int,
     *     month_paid_orders_count: int,
     * }
     */
    public function summaryMetrics(User $representative): array
    {
        $representativeId = (int) $representative->id;
        $monthStart = $this->jalaliMonthStart();
        $today = now(ShopDate::TIMEZONE)->startOfDay();

        $paymentsBase = Payment::query()
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereHas('order', fn (Builder $q) => $q->where('representative_id', $representativeId));

        $monthSales = (int) (clone $paymentsBase)
            ->where('paid_at', '>=', $monthStart)
            ->sum('amount');

        $todaySales = (int) (clone $paymentsBase)
            ->where('paid_at', '>=', $today)
            ->sum('amount');

        $monthPaidOrders = (int) (clone $paymentsBase)
            ->where('paid_at', '>=', $monthStart)
            ->distinct('order_id')
            ->count('order_id');

        $activeOrders = $this->currentOrdersQuery($representativeId);

        $proformaUnpaid = (clone $activeOrders)
            ->where('status', Order::STATUS_PROFORMA)
            ->where('final_amount', '>', 0)
            ->get()
            ->filter(fn (Order $order): bool => $order->remainingAmount() > 0)
            ->count();

        $expiringSoon = (clone $activeOrders)
            ->where('status', Order::STATUS_PROFORMA)
            ->where('stock_reserved', true)
            ->whereNotNull('stock_reserved_until')
            ->whereBetween('stock_reserved_until', [now(), now()->addHours(48)])
            ->count();

        return [
            'month_label' => $this->jalaliMonthLabel(),
            'month_sales' => $monthSales,
            'today_sales' => $todaySales,
            'active_orders_count' => (clone $activeOrders)->count(),
            'proforma_unpaid_count' => $proformaUnpaid,
            'expiring_reservations_count' => $expiringSoon,
            'customers_count' => User::query()
                ->where('created_by_representative_id', $representativeId)
                ->count(),
            'month_paid_orders_count' => $monthPaidOrders,
        ];
    }

    /**
     * @return array<string, int> gateway key => amount (toman)
     */
    public function salesByGateway(User $representative, ?Carbon $since = null): array
    {
        $representativeId = (int) $representative->id;
        $since ??= $this->jalaliMonthStart();

        $rows = Payment::query()
            ->where('status', Payment::STATUS_SUCCESS)
            ->where('paid_at', '>=', $since)
            ->whereHas('order', fn (Builder $q) => $q->where('representative_id', $representativeId))
            ->selectRaw('gateway, SUM(amount) as total')
            ->groupBy('gateway')
            ->orderByDesc('total')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $key = (string) ($row->gateway ?: 'unknown');
            $out[$key] = (int) $row->total;
        }

        return $out;
    }

    /**
     * @return array<string, int> catalog template label => amount (toman)
     */
    public function salesByCatalogTemplate(User $representative, ?Carbon $since = null): array
    {
        $since ??= $this->jalaliMonthStart();
        $representativeId = (int) $representative->id;

        $orderIds = Payment::query()
            ->where('status', Payment::STATUS_SUCCESS)
            ->where('paid_at', '>=', $since)
            ->whereHas('order', fn (Builder $q) => $q->where('representative_id', $representativeId))
            ->pluck('order_id')
            ->unique()
            ->values();

        if ($orderIds->isEmpty()) {
            return [];
        }

        $orders = Order::query()->whereIn('id', $orderIds)->get();
        Order::attachCatalogTemplateLabels($orders);

        $totals = [];
        foreach ($orders as $order) {
            $label = $order->catalogTemplateLabel();
            if ($label === '—' || $label === '') {
                $label = 'بدون طرح';
            }
            $totals[$label] = ($totals[$label] ?? 0) + (int) $order->final_amount;
        }

        arsort($totals);

        return $totals;
    }

    /**
     * @return Collection<int, array{gateway: string, label: string, total: int}>
     */
    public function gatewayBreakdownRows(User $representative): Collection
    {
        return collect($this->salesByGateway($representative))
            ->map(fn (int $total, string $gateway): array => [
                'gateway' => $gateway,
                'label' => ShopLabels::paymentMethod($gateway),
                'total' => $total,
            ])
            ->values();
    }
}
