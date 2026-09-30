@php
    /** @var \App\Support\SalesInvoice\SalesInvoiceDocument $document */
    $forPdf = ($context ?? 'web') === 'pdf';
@endphp

@if (! $forPdf)
    @once
        @push('styles')
            <link rel="stylesheet" href="{{ asset('shop/css/sales-invoice.css') }}">
        @endpush
    @endonce
@endif

<div class="si-document" dir="rtl">
    @if ($forPdf)
        <style>
            .si-document { font-family: vazirmatn, sans-serif; font-size: 11px; color: #000; }
            .si-document table { width: 100%; border-collapse: collapse; }
            .si-document th, .si-document td { border: 1px solid #000; padding: 5px 6px; vertical-align: top; }
            .si-title { text-align: center; font-size: 18px; font-weight: bold; margin: 8px 0 12px; }
            .si-meta td { border: none; padding: 2px 4px; font-size: 11px; }
            .si-party-label { background: #f3f4f6; font-weight: bold; text-align: center; }
            .si-lines th { background: #f3f4f6; text-align: center; font-size: 10px; }
            .si-lines td { text-align: center; font-size: 10px; }
            .si-lines td.si-desc { text-align: right; }
            .si-sum { font-weight: bold; background: #f9fafb; }
            .si-footer td { font-size: 10px; }
            .si-sign td { height: 48px; text-align: center; vertical-align: bottom; font-size: 10px; }
        </style>
    @endif

    <table class="si-meta">
        <tr>
            <td style="width: 50%; text-align: right;">شماره فاکتور: <strong>{{ $document->invoiceNumber }}</strong></td>
            <td style="width: 50%; text-align: left;">تاریخ فاکتور: <strong>{{ $document->invoiceDate }}</strong></td>
        </tr>
    </table>

    <div class="si-title">{{ $document->title }}</div>

    <table class="si-party">
        <tr>
            <td colspan="6" class="si-party-label">فروشنده</td>
        </tr>
        <tr>
            <td colspan="2"><span class="si-k">نام:</span> {{ $document->seller['name'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">تلفن:</span> {{ $document->seller['phone'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">شماره اقتصادی:</span> {{ $document->seller['economic_id'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="si-k">شناسه ثبت:</span> {{ $document->seller['registration_id'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">کد پستی:</span> {{ $document->seller['postal_code'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">نشانی:</span> {{ $document->seller['address'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="6" class="si-party-label">خریدار</td>
        </tr>
        <tr>
            <td colspan="2"><span class="si-k">نام:</span> {{ $document->buyer['name'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">کد مشتری:</span> {{ $document->buyer['code'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">تلفن:</span> {{ $document->buyer['phone'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="si-k">موبایل:</span> {{ $document->buyer['mobile'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">کد پستی:</span> {{ $document->buyer['postal_code'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">نشانی:</span> {{ $document->buyer['address'] ?? '—' }}</td>
        </tr>
    </table>

    <table class="si-lines" style="margin-top: 8px;">
        <thead>
            <tr>
                <th style="width: 4%;">ردیف</th>
                <th style="width: 12%;">کد کالا</th>
                <th style="width: 34%;">شرح کالا</th>
                <th style="width: 8%;">واحد</th>
                <th style="width: 8%;">تعداد</th>
                <th style="width: 17%;">بهای واحد (تومان)</th>
                <th style="width: 17%;">مبلغ کل (تومان)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->lines as $line)
                <tr>
                    <td>{{ $line['row'] }}</td>
                    <td dir="ltr">{{ $line['sku'] ?: '—' }}</td>
                    <td class="si-desc">{{ $line['name'] }}</td>
                    <td>{{ $line['unit'] }}</td>
                    <td>{{ number_format($line['quantity']) }}</td>
                    <td dir="ltr">{{ number_format($line['unit_price']) }}</td>
                    <td dir="ltr">{{ number_format($line['line_total']) }}</td>
                </tr>
            @endforeach
            <tr class="si-sum">
                <td colspan="4">جمع</td>
                <td>{{ number_format($document->quantityTotal) }}</td>
                <td></td>
                <td dir="ltr">{{ number_format($document->itemsTotal) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="si-footer" style="margin-top: 8px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                @if ($document->paymentMethodLabel)
                    <div><span class="si-k">نحوه پرداخت:</span> {{ $document->paymentMethodLabel }}</div>
                @endif
                @if ($document->freightLabel)
                    <div><span class="si-k">باربری / ارسال:</span> {{ $document->freightLabel }}</div>
                @endif
                @if ($document->notes)
                    <div style="margin-top: 6px;"><span class="si-k">توضیحات:</span> {{ $document->notes }}</div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top;">
                @if ($document->discountAmount > 0)
                    <div><span class="si-k">تخفیف:</span> {{ number_format($document->discountAmount) }} تومان</div>
                @endif
                @if ($document->shippingAmount > 0)
                    <div><span class="si-k">هزینه ارسال:</span> {{ number_format($document->shippingAmount) }} تومان</div>
                @endif
                <div style="margin-top: 6px; font-weight: bold;">
                    <span class="si-k">مبلغ قابل پرداخت:</span>
                    {{ number_format($document->payableAmount()) }} تومان
                </div>
            </td>
        </tr>
    </table>

    <table class="si-sign" style="margin-top: 10px;">
        <tr>
            <td style="width: 33%;">مهر و امضا مدیر فروش</td>
            <td style="width: 34%;">نام و امضا مدیر عامل</td>
            <td style="width: 33%;">مهر و امضا خریدار</td>
        </tr>
    </table>
</div>
