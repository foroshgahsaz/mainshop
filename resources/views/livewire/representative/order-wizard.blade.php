<div class="rep-order-wizard" wire:loading.class="opacity-75">
    <nav class="rep-wizard-steps" aria-label="مراحل سفارش">
        @foreach ([
            'customer' => 'مشتری',
            'family' => 'خانواده',
            'plant' => 'کارخانه',
            'brand' => 'برند',
            'template' => 'قالب',
            'product' => 'محصول',
            'review' => 'جمع‌بندی',
        ] as $key => $label)
            <button type="button"
                    wire:click="goToStep('{{ $key }}')"
                    @disabled($key !== 'customer' && $orderId === null)
                    class="rep-wizard-step {{ $step === $key ? 'is-active' : '' }}">
                {{ $label }}
            </button>
        @endforeach
    </nav>

    @if ($step === 'customer')
        <section class="rep-wizard-panel">
            <h2 class="rep-wizard-title">انتخاب مشتری</h2>
            <p class="rep-wizard-hint">حداکثر ۵۰ مشتری اخیر — بدون بارگذاری تصویر برای سرعت بیشتر.</p>
            <ul class="rep-choice-list">
                @forelse ($this->customers as $customer)
                    <li>
                        <button type="button" class="rep-choice-btn" wire:click="selectCustomer({{ $customer->id }})">
                            <span class="rep-choice-title">{{ $customer->name }}</span>
                            <span class="rep-choice-meta">{{ $customer->phone }}</span>
                        </button>
                    </li>
                @empty
                    <li class="rep-wizard-empty">مشتری ثبت نشده — از منوی مشتریان یک نفر اضافه کنید.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($step === 'family')
        <section class="rep-wizard-panel">
            <h2 class="rep-wizard-title">خانواده محصول</h2>
            <ul class="rep-choice-list">
                @forelse ($this->families as $family)
                    <li>
                        <button type="button" class="rep-choice-btn" wire:click="selectFamily({{ $family->id }})">
                            {{ $family->name }}
                        </button>
                    </li>
                @empty
                    <li class="rep-wizard-empty">خانواده فعالی با محصول موجود نیست.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($step === 'plant')
        <section class="rep-wizard-panel">
            <h2 class="rep-wizard-title">کارخانه</h2>
            <ul class="rep-choice-list">
                @forelse ($this->plants as $plant)
                    <li>
                        <button type="button" class="rep-choice-btn" wire:click="selectPlant({{ $plant->id }})">
                            {{ $plant->name }}
                        </button>
                    </li>
                @empty
                    <li class="rep-wizard-empty">کارخانه‌ای برای این خانواده پیدا نشد.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($step === 'brand')
        <section class="rep-wizard-panel">
            <h2 class="rep-wizard-title">برند</h2>
            <ul class="rep-choice-list">
                @forelse ($this->brands as $brand)
                    <li>
                        <button type="button" class="rep-choice-btn" wire:click="selectBrand({{ $brand->id }})">
                            {{ $brand->name }}
                        </button>
                    </li>
                @empty
                    <li class="rep-wizard-empty">برندی برای انتخاب‌های قبلی نیست.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($step === 'template')
        <section class="rep-wizard-panel">
            <h2 class="rep-wizard-title">قالب محصول</h2>
            <ul class="rep-choice-list">
                @forelse ($this->templates as $template)
                    <li>
                        <button type="button" class="rep-choice-btn" wire:click="selectTemplate({{ $template->id }})">
                            {{ $template->name }}
                        </button>
                    </li>
                @empty
                    <li class="rep-wizard-empty">قالبی پیدا نشد.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($step === 'product')
        <section class="rep-wizard-panel">
            <h2 class="rep-wizard-title">انتخاب محصول</h2>
            <input type="search"
                   wire:model.live.debounce.400ms="productSearch"
                   class="rep-wizard-search"
                   placeholder="جستجو نام یا کد کالا…"
                   autocomplete="off">
            <div class="rep-wizard-qty">
                <label>تعداد</label>
                <input type="number" min="1" max="999" wire:model="quantity" class="rep-wizard-qty-input">
            </div>
            <ul class="rep-product-list">
                @php $products = $this->products; @endphp
                @if ($products)
                    @forelse ($products as $product)
                        <li class="rep-product-row">
                            <div>
                                <div class="rep-choice-title">{{ $product->name }}</div>
                                <div class="rep-choice-meta">
                                    {{ $product->sku ?: '—' }} · {{ $this->formatMoney($product->effective_price) }}
                                    · موجودی {{ $product->stock }}
                                </div>
                            </div>
                            <button type="button" class="rep-btn-primary" wire:click="addProduct({{ $product->id }})">
                                افزودن
                            </button>
                        </li>
                    @empty
                        <li class="rep-wizard-empty">محصولی مطابق فیلترها نیست.</li>
                    @endforelse
                    <div class="rep-pagination">
                        @if (! $products->onFirstPage())
                            <button type="button" class="rep-btn-secondary" wire:click="previousPage('page')">صفحه قبل</button>
                        @endif
                        @if ($products->hasMorePages())
                            <button type="button" class="rep-btn-secondary" wire:click="nextPage('page')">صفحه بعد</button>
                        @endif
                    </div>
                @endif
            </ul>
        </section>
    @endif

    @if ($step === 'review')
        <section class="rep-wizard-panel">
            <h2 class="rep-wizard-title">جمع‌بندی پیش‌سفارش</h2>
            @php $order = $this->draftOrder; @endphp
            @if ($order)
                <p class="rep-wizard-hint">
                    مشتری: <strong>{{ $order->user?->name }}</strong> ({{ $order->user?->phone }})
                </p>
                <ul class="rep-product-list">
                    @forelse ($order->items as $item)
                        @php
                            $lineQty = (int) ($lineQuantities[$item->id] ?? $item->quantity);
                            $lineTotal = (int) $item->price * $lineQty;
                        @endphp
                        <li class="rep-product-row" wire:key="rep-line-{{ $item->id }}">
                            <div class="rep-product-row-main">
                                <div class="rep-choice-title">{{ $item->product_name }}</div>
                                <div class="rep-choice-meta">
                                    {{ $lineQty }} × {{ $this->formatMoney($item->price) }}
                                    = {{ $this->formatMoney($lineTotal) }}
                                </div>
                            </div>
                            <div class="rep-item-actions">
                                <div class="rep-qty-stepper">
                                    <span class="rep-item-qty-label">تعداد</span>
                                    <div class="rep-qty-stepper-control">
                                        <button type="button"
                                                class="rep-qty-stepper-btn"
                                                wire:click="decrementLineQuantity({{ $item->id }})"
                                                aria-label="کاهش تعداد">−</button>
                                        <input type="number"
                                               class="rep-qty-stepper-input"
                                               min="1"
                                               max="999"
                                               inputmode="numeric"
                                               wire:model.live="lineQuantities.{{ $item->id }}">
                                        <button type="button"
                                                class="rep-qty-stepper-btn"
                                                wire:click="incrementLineQuantity({{ $item->id }})"
                                                aria-label="افزایش تعداد">+</button>
                                    </div>
                                </div>
                                <button type="button"
                                        class="rep-btn-danger"
                                        wire:click="removeItem({{ $item->id }})">
                                    حذف
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="rep-wizard-empty">هنوز محصولی اضافه نشده.</li>
                    @endforelse
                </ul>
                <div class="rep-fulfillment-grid">
                    <div class="rep-fulfillment-field">
                        <label class="rep-fulfillment-label" for="freightCarrierSearch">باربری</label>
                        @if ($this->freightCarriers->isNotEmpty())
                            <input type="search"
                                   id="freightCarrierSearch"
                                   class="rep-fulfillment-search"
                                   wire:model.live.debounce.250ms="freightCarrierSearch"
                                   placeholder="جستجو نام، شماره، شهر یا استان…"
                                   autocomplete="off">
                        @endif
                        <select id="freightCarrierSelect"
                                class="rep-fulfillment-select rep-fulfillment-select-list"
                                wire:model="freightCarrierId"
                                @disabled($this->freightCarriers->isEmpty())>
                            <option value="">انتخاب باربری…</option>
                            @forelse ($this->filteredFreightCarriers as $carrier)
                                <option value="{{ $carrier->id }}">{{ $carrier->displayLabel() }}</option>
                            @empty
                                @if ($this->freightCarriers->isNotEmpty())
                                    <option value="" disabled>موردی برای «{{ $freightCarrierSearch }}» نیست</option>
                                @endif
                            @endforelse
                        </select>
                        @error('freight_carrier_id') <p class="rep-wizard-error">{{ $message }}</p> @enderror
                        @if ($this->freightCarriers->isEmpty())
                            <p class="rep-wizard-hint">باربری فعالی ثبت نشده — از پنل مدیریت، منوی ارسال → باربری‌ها را تعریف کنید.</p>
                        @endif
                    </div>
                    <div class="rep-fulfillment-field">
                        <label class="rep-fulfillment-label" for="paymentGatewaySelect">درگاه پرداخت</label>
                        <select id="paymentGatewaySelect"
                                class="rep-fulfillment-select"
                                wire:model="paymentGateway">
                            @forelse ($this->paymentGateways as $gateway)
                                <option value="{{ $gateway['name'] }}">{{ $gateway['label'] }}</option>
                            @empty
                                <option value="">درگاه فعالی نیست</option>
                            @endforelse
                        </select>
                        @error('payment_gateway') <p class="rep-wizard-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="rep-order-totals">
                    <div>جمع اقلام: {{ $this->formatMoney((int) $order->total_amount) }}</div>
                    @if ($order->freightCarrier)
                        <div>باربری: {{ $order->freightCarrier->displayLabel() }}</div>
                    @endif
                    <div>درگاه: {{ \App\Support\ShopLabels::paymentMethod($order->payment_method) }}</div>
                    <div class="rep-order-final">مبلغ نهایی: {{ $this->formatMoney((int) $order->final_amount) }}</div>
                </div>
                <p class="rep-wizard-hint rep-review-hint">
                    پس از تغییر تعداد یا باربری/درگاه، <strong>بروزرسانی</strong> را بزنید؛ سپس ثبت پیش‌فاکتور.
                </p>
                <div class="rep-wizard-actions rep-review-actions">
                    <button type="button" class="rep-btn-secondary" wire:click="goToStep('product')">افزودن محصول دیگر</button>
                    <button type="button"
                            class="rep-btn-update"
                            wire:click="refreshReviewTotals"
                            wire:loading.attr="disabled"
                            wire:target="refreshReviewTotals,finishDraft">
                        <span wire:loading.remove wire:target="refreshReviewTotals">بروزرسانی</span>
                        <span wire:loading wire:target="refreshReviewTotals">در حال محاسبه…</span>
                    </button>
                    <button type="button"
                            class="rep-btn-primary"
                            wire:click="finishDraft"
                            wire:loading.attr="disabled"
                            wire:target="finishDraft,refreshReviewTotals">
                        <span wire:loading.remove wire:target="finishDraft">ثبت پیش‌فاکتور (بدون ویرایش بعدی)</span>
                        <span wire:loading wire:target="finishDraft">در حال ثبت…</span>
                    </button>
                </div>
            @endif
            @error('order') <p class="rep-wizard-error">{{ $message }}</p> @enderror
            @error('quantity') <p class="rep-wizard-error">{{ $message }}</p> @enderror
        </section>
    @endif
</div>
