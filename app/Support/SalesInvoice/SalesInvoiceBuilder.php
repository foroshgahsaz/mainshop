<?php

namespace App\Support\SalesInvoice;

use App\Models\Order;
use App\Support\ShopLabels;
use Illuminate\Support\Collection;

class SalesInvoiceBuilder
{
    /**
     * @param  array<string, mixed>  $site
     */
    public static function fromOrder(Order $order, string $title, array $site): SalesInvoiceDocument
    {
        $order->loadMissing(['items', 'user', 'address.provinceModel', 'address.cityModel', 'freightCarrier', 'representative']);

        $lines = [];
        $qtyTotal = 0;

        foreach ($order->items as $index => $item) {
            $lines[] = [
                'row' => $index + 1,
                'sku' => (string) ($item->sku ?? ''),
                'name' => (string) $item->product_name,
                'unit' => 'عدد',
                'quantity' => (int) $item->quantity,
                'unit_price' => (int) $item->price,
                'line_total' => (int) $item->total_price,
            ];
            $qtyTotal += (int) $item->quantity;
        }

        $address = $order->address;
        $buyerAddress = $address
            ? trim(implode(' — ', array_filter([
                $address->address,
                $address->cityModel?->name ?? $address->city,
                $address->provinceModel?->name ?? $address->province,
            ])))
            : null;

        $buyer = [
            'name' => $order->user?->name,
            'code' => $order->user?->id ? str_pad((string) $order->user->id, 5, '0', STR_PAD_LEFT) : null,
            'phone' => $order->user?->phone,
            'mobile' => $order->user?->phone,
            'economic_id' => null,
            'postal_code' => $address?->postal_code,
            'address' => $buyerAddress,
        ];

        $seller = [
            'name' => (string) ($site['name'] ?? config('app.name')),
            'phone' => (string) ($site['phone'] ?? ''),
            'economic_id' => null,
            'registration_id' => null,
            'postal_code' => null,
            'address' => (string) ($site['address'] ?? ''),
        ];

        $notes = $order->representative
            ? 'نماینده: '.$order->representative->name.' — کد: '.$order->tracking_code
            : null;

        return new SalesInvoiceDocument(
            title: $title,
            invoiceNumber: (string) $order->tracking_code,
            invoiceDate: $order->updated_at?->format('Y/m/d H:i') ?? now()->format('Y/m/d H:i'),
            seller: $seller,
            buyer: $buyer,
            lines: $lines,
            quantityTotal: $qtyTotal,
            itemsTotal: (int) $order->total_amount,
            discountAmount: (int) $order->discount_amount,
            shippingAmount: (int) $order->shipping_amount,
            finalAmount: (int) $order->final_amount,
            paymentMethodLabel: ShopLabels::paymentMethod((string) $order->payment_method),
            freightLabel: $order->freightCarrier?->displayLabel(),
            notes: $notes,
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $site
     */
    public static function fromCartItems(Collection|array $items, array $summary, array $site): SalesInvoiceDocument
    {
        $collection = $items instanceof Collection ? $items : collect($items);

        $lines = [];
        $qtyTotal = 0;

        foreach ($collection->values() as $index => $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $price = (int) ($item['price'] ?? 0);
            $lines[] = [
                'row' => $index + 1,
                'sku' => (string) ($item['sku'] ?? ''),
                'name' => (string) ($item['product_name'] ?? ''),
                'unit' => 'عدد',
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => $price * $qty,
            ];
            $qtyTotal += $qty;
        }

        $subtotal = (int) ($summary['subtotal'] ?? 0);
        $discount = (int) ($summary['discount'] ?? 0);
        $shipping = (int) ($summary['shipping'] ?? 0);
        $total = (int) ($summary['total'] ?? $subtotal - $discount + $shipping);

        return new SalesInvoiceDocument(
            title: 'فاکتور فروش',
            invoiceNumber: 'سبد-'.now()->format('YmdHis'),
            invoiceDate: now()->format('Y/m/d H:i'),
            seller: [
                'name' => (string) ($site['name'] ?? config('app.name')),
                'phone' => (string) ($site['phone'] ?? ''),
                'economic_id' => null,
                'registration_id' => null,
                'postal_code' => null,
                'address' => (string) ($site['address'] ?? ''),
            ],
            buyer: [
                'name' => auth()->user()?->name ?? 'مهمان',
                'code' => auth()->id() ? str_pad((string) auth()->id(), 5, '0', STR_PAD_LEFT) : null,
                'phone' => auth()->user()?->phone,
                'mobile' => auth()->user()?->phone,
                'economic_id' => null,
                'postal_code' => null,
                'address' => null,
            ],
            lines: $lines,
            quantityTotal: $qtyTotal,
            itemsTotal: $subtotal,
            discountAmount: $discount,
            shippingAmount: $shipping,
            finalAmount: $total,
            paymentMethodLabel: null,
            freightLabel: isset($summary['shipping_method']) ? (string) $summary['shipping_method'] : null,
            notes: 'پیش‌فاکتور سبد خرید — قبل از ثبت سفارش',
        );
    }
}
