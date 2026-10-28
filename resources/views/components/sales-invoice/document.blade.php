@php
    /** @var \App\Support\SalesInvoice\SalesInvoiceDocument $document */
    use App\Support\ShopLabels;

    $forPdf = ($context ?? 'web') === 'pdf';
    $siRial = static fn (int $toman): string => number_format(ShopLabels::invoiceAmountInRial($toman));
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
            .si-paid-summary td { font-size: 10px; padding: 6px 8px; }
            .si-paid-summary .si-k { font-weight: bold; }
        </style>
    @endif

    <table class="si-meta">
        <tr>
            <td class="si-meta__num">شماره فاکتور: <strong>{{ $document->invoiceNumber }}</strong></td>
            <td class="si-meta__date">تاریخ فاکتور: <strong>{{ $document->invoiceDate }}</strong></td>
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
            <td colspan="2"><span class="si-k">کد ملی:</span> {{ $document->buyer['national_code'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="si-k">تلفن:</span> {{ $document->buyer['phone'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">موبایل:</span> {{ $document->buyer['mobile'] ?? '—' }}</td>
            <td colspan="2"><span class="si-k">کد پستی:</span> {{ $document->buyer['postal_code'] ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="6"><span class="si-k">نشانی:</span> {{ $document->buyer['address'] ?? '—' }}</td>
        </tr>
    </table>

    <div class="si-lines-section">
        <table class="si-lines si-lines--table">
            <thead>
                <tr>
                    <th style="width: 4%;">ردیف</th>
                    <th style="width: 12%;">کد کالا</th>
                    <th style="width: 34%;">شرح کالا</th>
                    <th style="width: 8%;">واحد</th>
                    <th style="width: 8%;">تعداد</th>
                    <th style="width: 14%;">بهای واحد (ریال)</th>
                    @if ($document->showsLineDiscountColumn())
                        <th style="width: 12%;">تخفیف (ریال)</th>
                    @endif
                    <th style="width: 14%;">مبلغ کل (ریال)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($document->lines as $line)
                    @php($isAdjustment = ! $document->isProductLine($line))
                    <tr>
                        <td>{{ $line['row'] }}</td>
                        <td dir="ltr">{{ $line['sku'] ?: '—' }}</td>
                        <td class="si-desc">{{ $line['name'] }}</td>
                        <td>{{ $line['unit'] }}</td>
                        <td>{{ number_format($line['quantity']) }}</td>
                        <td dir="ltr">{{ $siRial($document->lineAmountForDisplay((int) $line['unit_price'], $isAdjustment)) }}</td>
                        @if ($document->showsLineDiscountColumn())
                            <td dir="ltr">{{ $siRial($document->lineAmountForDisplay((int) ($line['line_discount'] ?? 0), $isAdjustment)) }}</td>
                        @endif
                        <td dir="ltr">{{ $siRial($document->lineAmountForDisplay((int) $line['line_total'], $isAdjustment)) }}</td>
                    </tr>
                @endforeach
                @if ($document->showVatBreakdown && $document->productLinesGrossTotal() > 0)
                    @if ($document->productLinesVatAmount() > 0)
                        @php($vatToman = $document->productLinesVatAmount())
                        <tr class="si-sum">
                            <td colspan="4">ارزش افزوده ({{ $document->vatPercent() }}٪)</td>
                            <td>{{ number_format(1) }}</td>
                            <td dir="ltr">{{ $siRial($vatToman) }}</td>
                            @if ($document->showsLineDiscountColumn())
                                <td></td>
                            @endif
                            <td dir="ltr">{{ $siRial($vatToman) }}</td>
                        </tr>
                    @endif
                    <tr class="si-sum">
                        <td colspan="4">جمع کل</td>
                        <td></td>
                        <td></td>
                        @if ($document->showsLineDiscountColumn())
                            <td></td>
                        @endif
                        <td dir="ltr">{{ $siRial($document->productLinesGrossTotal()) }}</td>
                    </tr>
                @else
                    <tr class="si-sum">
                        <td colspan="4">جمع اقلام</td>
                        <td>{{ number_format($document->quantityTotal) }}</td>
                        <td></td>
                        @if ($document->showsLineDiscountColumn())
                            <td></td>
                        @endif
                        <td dir="ltr">{{ $siRial($document->itemsTotal) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if (! $forPdf)
        <div class="si-lines--cards" aria-label="اقلام فاکتور">
            @foreach ($document->lines as $line)
                @php($isAdjustment = ! $document->isProductLine($line))
                <article class="si-line-card">
                    <header class="si-line-card__head">
                        <span class="si-line-card__row">ردیف {{ $line['row'] }}</span>
                        @if (filled($line['sku'] ?? null))
                            <span class="si-line-card__sku" dir="ltr">{{ $line['sku'] }}</span>
                        @endif
                    </header>
                    <p class="si-line-card__name">{{ $line['name'] }}</p>
                    <dl class="si-line-card__grid">
                        <div>
                            <dt>واحد</dt>
                            <dd>{{ $line['unit'] }}</dd>
                        </div>
                        <div>
                            <dt>تعداد</dt>
                            <dd>{{ number_format($line['quantity']) }}</dd>
                        </div>
                        <div>
                            <dt>بهای واحد@if($document->showVatBreakdown && ! $isAdjustment) (بدون مالیات)@endif</dt>
                            <dd dir="ltr">{{ ShopLabels::formatInvoiceMoney($document->lineAmountForDisplay((int) $line['unit_price'], $isAdjustment)) }}</dd>
                        </div>
                        @if ($document->showsLineDiscountColumn())
                            <div>
                                <dt>تخفیف</dt>
                                <dd dir="ltr">{{ ShopLabels::formatInvoiceMoney($document->lineAmountForDisplay((int) ($line['line_discount'] ?? 0), $isAdjustment)) }}</dd>
                            </div>
                        @endif
                        <div class="si-line-card__total">
                            <dt>مبلغ کل@if($document->showVatBreakdown && ! $isAdjustment) (بدون مالیات)@endif</dt>
                            <dd dir="ltr">{{ ShopLabels::formatInvoiceMoney($document->lineAmountForDisplay((int) $line['line_total'], $isAdjustment)) }}</dd>
                        </div>
                    </dl>
                </article>
            @endforeach
            @if ($document->showVatBreakdown && $document->productLinesGrossTotal() > 0)
                @if ($document->productLinesVatAmount() > 0)
                    <div class="si-line-card si-line-card--sum">
                        <div class="si-line-card__sum-row">
                            <span>ارزش افزوده ({{ $document->vatPercent() }}٪)</span>
                            <strong dir="ltr">{{ ShopLabels::formatInvoiceMoney($document->productLinesVatAmount()) }}</strong>
                        </div>
                    </div>
                @endif
                <div class="si-line-card si-line-card--sum">
                    <div class="si-line-card__sum-row">
                        <span>جمع کل</span>
                        <strong dir="ltr">{{ ShopLabels::formatInvoiceMoney($document->productLinesGrossTotal()) }}</strong>
                    </div>
                </div>
            @else
                <div class="si-line-card si-line-card--sum">
                    <div class="si-line-card__sum-row">
                        <span>جمع اقلام</span>
                        <strong dir="ltr">{{ ShopLabels::formatInvoiceMoney($document->itemsTotal) }}</strong>
                    </div>
                    <div class="si-line-card__sum-qty">
                        تعداد کل: <strong>{{ number_format($document->quantityTotal) }}</strong>
                    </div>
                </div>
            @endif
        </div>
        @endif
    </div>

    <table class="si-footer">
        <tr>
            <td class="si-footer__notes">
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
            <td class="si-footer__totals">
                @if ($document->discountAmount > 0)
                    <div><span class="si-k">جمع تخفیف‌ها:</span> {{ ShopLabels::formatInvoiceMoney($document->discountAmount) }}</div>
                @endif
                @if ($document->feesTotal > 0)
                    <div><span class="si-k">جمع هزینه‌های اضافه:</span> {{ ShopLabels::formatInvoiceMoney($document->feesTotal) }}</div>
                @endif
                @if ($document->shippingAmount > 0)
                    <div><span class="si-k">هزینه ارسال:</span> {{ ShopLabels::formatInvoiceMoney($document->shippingAmount) }}</div>
                @endif
                <div style="margin-top: 6px; font-weight: bold;">
                    <span class="si-k">مبلغ قابل پرداخت:</span>
                    {{ ShopLabels::formatInvoiceMoney($document->payableAmount()) }}
                </div>
            </td>
        </tr>
    </table>

    @if ($document->showsSuccessfulPaymentsBlock())
        <table class="si-paid-summary" style="margin-top: 10px; width: 100%;">
            <tr>
                <td colspan="3" class="si-party-label">پرداخت‌های موفق ثبت‌شده</td>
            </tr>
            <tr>
                @if ($document->paidCashSuccessTomans > 0)
                    <td style="width: 50%;">
                        <span class="si-k">جمع نقدی موفق:</span>
                        {{ ShopLabels::formatInvoiceMoney($document->paidCashSuccessTomans) }}
                    </td>
                @endif
                @if ($document->paidCreditSuccessTomans > 0)
                    <td @if ($document->paidCashSuccessTomans <= 0) colspan="3" @endif style="width: 50%;">
                        <span class="si-k">جمع اعتباری موفق:</span>
                        {{ ShopLabels::formatInvoiceMoney($document->paidCreditSuccessTomans) }}
                    </td>
                @endif
            </tr>
        </table>
    @endif

    <table class="si-sign" style="margin-top: 10px;">
        <tr>
            <td style="width: 33%;">مهر و امضا مدیر فروش</td>
            <td style="width: 34%;">نام و امضا مدیر عامل</td>
            <td style="width: 33%;">مهر و امضا خریدار</td>
        </tr>
    </table>
</div>
