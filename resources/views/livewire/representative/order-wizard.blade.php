@php
    $wizardSteps = $this->visibleWizardSteps;
    $wizardStepKeys = array_keys($wizardSteps);
    $activeStepIndex = array_search($step, $wizardStepKeys, true);
    $catalogTrail = $this->catalogSelectionSummary;
@endphp
<div @class([
        'rep-order-wizard',
        'rep-order-wizard--review' => $step === 'review',
        'rep-order-wizard--product' => $step === 'product',
    ])
     wire:loading.class="opacity-75"
     @rep-product-images-modal-closed.window="document.documentElement.classList.remove('rep-modal-open')">
    <nav class="rep-wizard-stepper" aria-label="مراحل سفارش" data-rep-tour="rep-wizard-stepper">
        @foreach ($wizardSteps as $key => $label)
            @php
                $stepIndex = array_search($key, $wizardStepKeys, true);
                $isStepActive = $step === $key;
                $isStepComplete = $activeStepIndex !== false && $stepIndex !== false && $stepIndex < $activeStepIndex;
            @endphp
            <button type="button"
                    wire:click="goToStep('{{ $key }}')"
                    @disabled($key !== 'customer' && $orderId === null)
                    class="rep-wizard-stepper__item {{ $isStepActive ? 'is-active' : '' }} {{ $isStepComplete ? 'is-complete' : '' }}"
                    @if ($isStepActive) aria-current="step" @endif>
                <span class="rep-wizard-stepper__label">{{ $label }}</span>
            </button>
        @endforeach
    </nav>

    <div @class([
        'rep-wizard-stage',
        'rep-wizard-stage--products' => $step === 'product',
        'rep-wizard-stage--review' => $step === 'review',
    ])>
    @if ($step === 'customer')
        <section class="rep-wizard-panel" data-rep-tour="rep-wizard-customer">
            <header class="rep-wizard-header">
                <h2 class="rep-wizard-title">انتخاب مشتری</h2>
                <p class="rep-wizard-hint">حداکثر ۵۰ مشتری اخیر نمایش داده می‌شود. در صورت نیاز از منوی «مشتریان» شخص جدید ثبت کنید.</p>
            </header>
            <div class="rep-wizard-panel__body">
            <ul class="rep-plan-list" role="list">
                @forelse ($this->customers as $customer)
                    <li>
                        <button type="button"
                                class="rep-plan-row"
                                wire:click="selectCustomer({{ $customer->id }})">
                            <span class="rep-plan-row__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <span class="rep-plan-row__body">
                                <span class="rep-plan-row__title">{{ $customer->name }}</span>
                                <span class="rep-plan-row__meta">{{ $customer->phone }}</span>
                            </span>
                            <span class="rep-plan-row__radio" aria-hidden="true"></span>
                        </button>
                    </li>
                @empty
                    <li class="rep-wizard-empty">مشتری ثبت نشده — از منوی مشتریان یک نفر اضافه کنید.</li>
                @endforelse
            </ul>
            </div>
        </section>
    @endif

    @if ($step === 'family')
        <section class="rep-wizard-panel" data-rep-tour="rep-wizard-family">
            <header class="rep-wizard-header">
                <h2 class="rep-wizard-title">خانواده محصول</h2>
                <p class="rep-wizard-hint">فقط خانواده‌هایی که محصول فعال و موجود دارند نمایش داده می‌شوند.</p>
            </header>
            <div class="rep-wizard-panel__body">
            <div class="rep-chip-grid" role="list">
                @forelse ($this->families as $family)
                    <button type="button"
                            role="listitem"
                            class="rep-chip {{ $familyId === $family->id ? 'is-selected' : '' }}"
                            wire:click="selectFamily({{ $family->id }})">
                        {{ $family->name }}
                    </button>
                @empty
                    <p class="rep-wizard-empty">خانواده فعالی با محصول موجود نیست.</p>
                @endforelse
            </div>
            </div>
        </section>
    @endif

    @if ($step === 'plant')
        <section class="rep-wizard-panel" data-rep-tour="rep-wizard-plant">
            <header class="rep-wizard-header">
                <h2 class="rep-wizard-title">کارخانه</h2>
                @if ($catalogTrail['family'])
                    <p class="rep-wizard-hint">خانواده: <strong>{{ $catalogTrail['family'] }}</strong> — کارخانه‌های مرتبط با این خانواده.</p>
                @endif
            </header>
            <div class="rep-wizard-panel__body">
            <div class="rep-chip-grid" role="list">
                @forelse ($this->plants as $plant)
                    <button type="button"
                            role="listitem"
                            class="rep-chip {{ $plantId === $plant->id ? 'is-selected' : '' }}"
                            wire:click="selectPlant({{ $plant->id }})">
                        {{ $plant->name }}
                    </button>
                @empty
                    <p class="rep-wizard-empty">کارخانه‌ای برای این خانواده پیدا نشد.</p>
                @endforelse
            </div>
            </div>
        </section>
    @endif

    @if ($step === 'brand')
        <section class="rep-wizard-panel" data-rep-tour="rep-wizard-brand">
            <header class="rep-wizard-header">
                <h2 class="rep-wizard-title">برند</h2>
                @if ($catalogTrail['family'] || $catalogTrail['plant'])
                    <p class="rep-wizard-hint">
                        @if ($catalogTrail['family']){{ $catalogTrail['family'] }}@endif
                        @if ($catalogTrail['plant']) · {{ $catalogTrail['plant'] }}@endif
                    </p>
                @endif
            </header>
            <div class="rep-wizard-panel__body">
            <div class="rep-chip-grid" role="list">
                @forelse ($this->brands as $brand)
                    <button type="button"
                            role="listitem"
                            class="rep-chip {{ $brandId === $brand->id ? 'is-selected' : '' }}"
                            wire:click="selectBrand({{ $brand->id }})">
                        {{ $brand->name }}
                    </button>
                @empty
                    <p class="rep-wizard-empty">برندی برای انتخاب‌های قبلی نیست.</p>
                @endforelse
            </div>
            </div>
        </section>
    @endif

    @if ($step === 'template')
        <section class="rep-wizard-panel" data-rep-tour="rep-wizard-template">
            <header class="rep-wizard-header">
                <h2 class="rep-wizard-title">قالب محصول</h2>
                @if ($catalogTrail['brand'])
                    <p class="rep-wizard-hint">برند: <strong>{{ $catalogTrail['brand'] }}</strong> — قالب‌های فعال با موجودی برای این برند.</p>
                @endif
            </header>
            <div class="rep-wizard-panel__body">
            <div class="rep-chip-grid" role="list">
                @forelse ($this->templates as $template)
                    <button type="button"
                            role="listitem"
                            class="rep-chip {{ $templateId === $template->id ? 'is-selected' : '' }}"
                            wire:click="selectTemplate({{ $template->id }})">
                        {{ $template->name }}
                    </button>
                @empty
                    <p class="rep-wizard-empty">قالبی برای این ترکیب خانواده، کارخانه و برند نیست.</p>
                @endforelse
            </div>
            </div>
        </section>
    @endif

    @if ($step === 'product')
        <section class="rep-wizard-panel" data-rep-tour="rep-wizard-product">
            <header class="rep-wizard-header">
                <h2 class="rep-wizard-title">انتخاب محصول</h2>
                @if ($catalogTrail['family'] || $catalogTrail['plant'] || $catalogTrail['brand'] || $catalogTrail['template'])
                    <p class="rep-wizard-hint rep-catalog-trail">
                        {{ collect([$catalogTrail['family'], $catalogTrail['plant'], $catalogTrail['brand'], $catalogTrail['template']])->filter()->implode(' · ') }}
                    </p>
                @endif
            </header>
            <div class="rep-wizard-toolbar">
                <label class="rep-field rep-field--grow">
                    <span class="rep-field__label">جستجو</span>
                    <input type="search"
                           wire:model.live.debounce.400ms="productSearch"
                           class="rep-field__input"
                           placeholder="نام یا کد کالا…"
                           autocomplete="off">
                </label>
                <label class="rep-field rep-field--qty">
                    <span class="rep-field__label">تعداد افزودن</span>
                    <input type="number" min="1" max="999" wire:model="quantity" class="rep-field__input rep-field__input--qty">
                </label>
            </div>
            <div class="rep-wizard-panel__body rep-wizard-panel__body--products">
            @php $products = $this->products; @endphp
            @if ($products)
                <ul class="rep-product-picker" role="list">
                    @forelse ($products as $product)
                        @php $thumbUrl = $this->productListImageUrl($product); @endphp
                        <li class="rep-product-card" wire:key="rep-product-{{ $product->id }}">
                            <button type="button"
                                    class="rep-product-card__media"
                                    wire:click="openProductImages({{ $product->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="openProductImages"
                                    aria-label="مشاهده تصاویر {{ $product->name }}">
                                @if ($thumbUrl)
                                    <img src="{{ $thumbUrl }}"
                                         alt=""
                                         loading="lazy"
                                         decoding="async"
                                         width="400"
                                         height="300">
                                @else
                                    <span class="rep-product-card__media-placeholder" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                                        <span>بدون تصویر</span>
                                    </span>
                                @endif
                                @if ($product->images->count() > 1)
                                    <span class="rep-product-card__media-badge">{{ $product->images->count() }} تصویر</span>
                                @endif
                            </button>
                            <div class="rep-product-card__body">
                                <h3 class="rep-product-card__title">{{ $product->name }}</h3>
                                <dl class="rep-product-card__meta">
                                    <div><dt>کد</dt><dd>{{ $product->sku ?: '—' }}</dd></div>
                                    <div><dt>قیمت</dt><dd>{{ $this->formatMoney($product->effective_price) }}</dd></div>
                                    <div><dt>موجودی</dt><dd>{{ $product->stock }}</dd></div>
                                </dl>
                            </div>
                            <div class="rep-product-card__foot">
                                <button type="button"
                                        class="rep-btn-secondary rep-btn-secondary--compact"
                                        wire:click="openProductImages({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="openProductImages">
                                    <span wire:loading.remove wire:target="openProductImages">گالری</span>
                                    <span wire:loading wire:target="openProductImages">…</span>
                                </button>
                                <button type="button" class="rep-btn-primary" wire:click="addProduct({{ $product->id }})">
                                    افزودن به سفارش
                                </button>
                            </div>
                        </li>
                    @empty
                        <li class="rep-wizard-empty rep-wizard-empty--full">محصولی مطابق فیلترهای انتخاب‌شده نیست.</li>
                    @endforelse
                </ul>
                @if ($products->hasPages())
                    <nav class="rep-product-picker__pagination" aria-label="صفحه‌بندی محصولات">
                        {{ $products->links(data: ['scrollTo' => false]) }}
                    </nav>
                @endif
            @endif
            </div>
        </section>
    @endif

    @if ($step === 'review')
        <section class="rep-wizard-panel">
            <header class="rep-wizard-header">
                <h2 class="rep-wizard-title">جمع‌بندی پیش‌سفارش</h2>
                <p class="rep-wizard-hint">باربری و درگاه را انتخاب کنید، سپس پیش‌فاکتور را ثبت کنید.</p>
            </header>
            <div class="rep-wizard-panel__body rep-wizard-panel__body--review">
            @php $order = $this->draftOrder; @endphp
            @if ($order)
                <p class="rep-wizard-hint">
                    مشتری: <strong>{{ $order->user?->name }}</strong> ({{ $order->user?->phone }})
                </p>
                @php
                    $catalog = $this->catalogSelectionSummary;
                @endphp
                @if ($catalog['family'] || $catalog['plant'] || $catalog['brand'] || $catalog['template'])
                    <dl class="rep-catalog-summary rep-catalog-summary--cards">
                        @if ($catalog['family'])
                            <div><dt>خانواده</dt><dd>{{ $catalog['family'] }}</dd></div>
                        @endif
                        @if ($catalog['plant'])
                            <div><dt>کارخانه</dt><dd>{{ $catalog['plant'] }}</dd></div>
                        @endif
                        @if ($catalog['brand'])
                            <div><dt>برند</dt><dd>{{ $catalog['brand'] }}</dd></div>
                        @endif
                        @if ($catalog['template'])
                            <div><dt>قالب</dt><dd>{{ $catalog['template'] }}</dd></div>
                        @endif
                    </dl>
                @endif
                <ul class="rep-product-list" data-rep-tour="rep-wizard-review-items">
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
                    <div class="rep-fulfillment-field" data-rep-tour="rep-wizard-freight">
                        <label class="rep-fulfillment-label" for="freightCarrierSelectTrigger">باربری</label>
                        @if ($this->freightCarriers->isEmpty())
                            <button type="button"
                                    id="freightCarrierSelectTrigger"
                                    class="rep-searchable-select__trigger"
                                    disabled>
                                انتخاب باربری…
                            </button>
                            <p class="rep-wizard-hint">باربری فعالی ثبت نشده — از پنل مدیریت، منوی ارسال → باربری‌ها را تعریف کنید.</p>
                        @else
                            <div class="rep-searchable-select"
                                 wire:ignore.self
                                 x-data="{
                                     open: false,
                                     search: '',
                                     selectedId: @entangle('freightCarrierId').live,
                                     carriers: @js($this->freightCarriers->map(fn ($carrier) => [
                                         'id' => $carrier->id,
                                         'label' => $carrier->displayLabel(),
                                     ])->values()->all()),
                                     get filteredCarriers() {
                                         const query = this.search.trim().toLocaleLowerCase('fa');
                                         if (query === '') {
                                             return this.carriers;
                                         }
                                         return this.carriers.filter((carrier) => carrier.label.toLocaleLowerCase('fa').includes(query));
                                     },
                                     get selectedLabel() {
                                         const match = this.carriers.find((carrier) => carrier.id === this.selectedId);
                                         return match ? match.label : '';
                                     },
                                     toggle() {
                                         this.open = !this.open;
                                         if (this.open) {
                                             this.$nextTick(() => this.$refs.freightSearch?.focus());
                                         } else {
                                             this.search = '';
                                         }
                                     },
                                     close() {
                                         this.open = false;
                                         this.search = '';
                                     },
                                     pick(id) {
                                         this.selectedId = id;
                                         this.close();
                                     },
                                 }"
                                 @click.outside="close()"
                                 @keydown.escape.window="close()">
                                <button type="button"
                                        id="freightCarrierSelectTrigger"
                                        class="rep-searchable-select__trigger"
                                        :class="{ 'is-open': open }"
                                        @click="toggle()"
                                        aria-haspopup="listbox"
                                        :aria-expanded="open">
                                    <span class="rep-searchable-select__value" x-text="selectedLabel || 'انتخاب باربری…'"></span>
                                </button>
                                <div class="rep-searchable-select__dropdown" x-show="open" x-cloak>
                                    <div class="rep-searchable-select__search-wrap">
                                        <input type="search"
                                               x-ref="freightSearch"
                                               class="rep-searchable-select__search"
                                               x-model="search"
                                               placeholder="جستجو نام، شماره، شهر یا استان…"
                                               autocomplete="off"
                                               @keydown.enter.prevent>
                                    </div>
                                    <ul class="rep-searchable-select__list" role="listbox">
                                        <template x-for="carrier in filteredCarriers" :key="carrier.id">
                                            <li>
                                                <button type="button"
                                                        class="rep-searchable-select__option"
                                                        :class="{ 'is-selected': selectedId === carrier.id }"
                                                        role="option"
                                                        :aria-selected="selectedId === carrier.id"
                                                        @click="pick(carrier.id)"
                                                        x-text="carrier.label"></button>
                                            </li>
                                        </template>
                                        <li x-show="filteredCarriers.length === 0" class="rep-searchable-select__empty">
                                            موردی برای جستجو پیدا نشد.
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        @endif
                        @error('freight_carrier_id') <p class="rep-wizard-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="rep-fulfillment-field rep-fulfillment-field--gateways" data-rep-tour="rep-wizard-gateways">
                        <span class="rep-fulfillment-label" id="paymentGatewayLegend">درگاه پرداخت</span>
                        @if (count($this->paymentGateways) === 0)
                            <p class="rep-wizard-empty">درگاه فعالی نیست</p>
                        @else
                            <ul class="rep-gateway-list rep-gateway-list--compact" role="radiogroup" aria-labelledby="paymentGatewayLegend">
                                @foreach ($this->paymentGateways as $gateway)
                                    <li wire:key="rep-gateway-{{ $gateway['name'] }}">
                                        <label class="rep-gateway-option {{ $paymentGateway === $gateway['name'] ? 'is-selected' : '' }}">
                                            <input type="radio"
                                                   class="rep-gateway-option__input"
                                                   name="repPaymentGateway"
                                                   value="{{ $gateway['name'] }}"
                                                   wire:model.live="paymentGateway">
                                            <x-checkout-option-icon :src="$gateway['icon'] ?? null" :alt="$gateway['label']" />
                                            <span class="rep-gateway-option__body">
                                                <span class="rep-gateway-option__title">{{ $gateway['label'] }}</span>
                                                @if (! empty($gateway['description']))
                                                    <span class="rep-gateway-option__meta">{{ $gateway['description'] }}</span>
                                                @endif
                                            </span>
                                            <span class="rep-plan-row__radio" aria-hidden="true"></span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @error('payment_gateway') <p class="rep-wizard-error">{{ $message }}</p> @enderror
                        @if ($this->selectedPaymentGatewayType === \App\Services\Payment\PaymentGatewayCatalog::TYPE_CREDIT && $order)
                            <div class="rep-split-plan-card" role="region" aria-label="طرح پرداخت اعتبار و نقد" data-rep-tour="rep-wizard-split-pay">
                                <div class="rep-split-plan-card__glow" aria-hidden="true"></div>
                                <div class="rep-split-plan-card__ribbon" aria-hidden="true">اعتبار + نقد</div>
                                <div class="rep-split-plan-card__head">
                                    <div class="rep-split-plan-card__head-main">
                                        <span class="rep-split-plan-card__icon" aria-hidden="true">
                                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2" opacity="0.35"/>
                                                <path d="M16 28h16M16 22h10M16 16h16" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                                            </svg>
                                        </span>
                                        <div>
                                            <span class="rep-split-plan-card__badge">پرداخت ترکیبی</span>
                                            <h3 class="rep-split-plan-card__title">تقسیم مبلغ بین اعتبار و درگاه نقدی</h3>
                                        </div>
                                    </div>
                                    <p class="rep-split-plan-card__lead">
                                        بخشی <strong>اعتباری</strong> (باجت/…) و مابقی <strong>نقدی</strong> — چند پرداخت نقدی مجاز است.
                                    </p>
                                </div>
                                <label class="rep-field rep-field--grow">
                                    <span class="rep-field__label">مبلغ اعتباری (ریال)</span>
                                    <input type="text"
                                           inputmode="numeric"
                                           wire:model.live="plannedCreditRial"
                                           class="rep-field__input rep-split-plan-card__amount rep-field__input--rial-lg"
                                           placeholder="مثلاً {{ number_format((int) $order->final_amount * 10) }}"
                                           dir="ltr"
                                           autocomplete="off">
                                </label>
                                @php
                                    $plannedCreditTomans = (int) ($plannedCreditAmount ?? 0);
                                    $cashRemainTomans = max(0, (int) $order->final_amount - $plannedCreditTomans);
                                @endphp
                                @if ($cashRemainTomans > 0 && $plannedCreditTomans > 0 && $plannedCreditTomans < (int) $order->final_amount)
                                    <div class="rep-split-cash-pay">
                                        <p class="rep-wizard-hint">
                                            مابقی نقدی: <strong>{{ $this->formatMoney($cashRemainTomans) }}</strong>
                                            — می‌توانید پس از ثبت، نقدی بزنید یا همین‌جا ثبت + پرداخت نقدی کنید.
                                        </p>
                                        <div class="rep-split-cash-pay__row">
                                            <label class="rep-field rep-field--grow">
                                                <span class="rep-field__label">مبلغ پرداخت نقدی (ریال)</span>
                                                <input type="text"
                                                       inputmode="numeric"
                                                       wire:model.live="cashPayRial"
                                                       class="rep-field__input rep-field__input--rial-lg"
                                                       placeholder="{{ number_format($cashRemainTomans * 10) }}"
                                                       dir="ltr"
                                                       autocomplete="off">
                                            </label>
                                            <button type="button"
                                                    class="rep-btn-primary rep-split-cash-pay__btn"
                                                    wire:click="finishDraftAndPayCash"
                                                    wire:loading.attr="disabled"
                                                    wire:target="finishDraftAndPayCash">
                                                <span wire:loading.remove wire:target="finishDraftAndPayCash">ثبت پیش‌فاکتور و پرداخت نقدی</span>
                                                <span wire:loading wire:target="finishDraftAndPayCash">در حال اتصال…</span>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                                @if (count($this->cashGateways) === 0)
                                    <p class="rep-wizard-empty">درگاه نقدی فعالی برای مابقی مبلغ نیست.</p>
                                @else
                                    <div class="rep-cash-gateway-section">
                                        <span class="rep-cash-gateway-section__title" id="repCashGatewayLegend">
                                            درگاه نقدی (برای مابقی مبلغ)
                                        </span>
                                        <p class="rep-cash-gateway-section__hint">روی آیکون درگاه کلیک کنید:</p>
                                        <ul class="rep-cash-gateway-grid" role="radiogroup" aria-labelledby="repCashGatewayLegend">
                                            @foreach ($this->cashGateways as $cashGw)
                                                <li wire:key="rep-cash-gw-{{ $cashGw['name'] }}">
                                                    <label @class([
                                                        'rep-cash-gateway-tile',
                                                        'is-selected' => $cashGateway === $cashGw['name'],
                                                    ])>
                                                        <input type="radio"
                                                               class="rep-gateway-option__input"
                                                               name="repCashGateway"
                                                               value="{{ $cashGw['name'] }}"
                                                               wire:model.live="cashGateway">
                                                        <span class="rep-cash-gateway-tile__icon-wrap">
                                                            <x-checkout-option-icon :src="$cashGw['icon'] ?? null" :alt="$cashGw['label']" />
                                                        </span>
                                                        <span class="rep-cash-gateway-tile__label">{{ $cashGw['label'] }}</span>
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                <div class="rep-coupon-box" role="region" aria-label="کد تخفیف" data-rep-tour="rep-wizard-coupon">
                    <span class="rep-coupon-box__label">کد تخفیف</span>
                    <p class="rep-wizard-hint rep-coupon-box__hint">
                        همان قوانین فروشگاه (حداقل سبد، درگاه مجاز، سقف استفاده برای مشتری) اعمال می‌شود.
                    </p>
                    <div class="rep-coupon-box__row">
                        <input type="text"
                               wire:model="couponCode"
                               class="rep-field__input rep-coupon-box__input @error('coupon_code') rep-field__input--error @enderror"
                               placeholder="کد را وارد کنید"
                               autocomplete="off"
                               @disabled($order->coupon_id !== null)>
                        @if ($order->coupon_id !== null)
                            <button type="button"
                                    class="rep-btn-secondary"
                                    wire:click="removeCoupon"
                                    wire:loading.attr="disabled">
                                حذف
                            </button>
                        @else
                            <button type="button"
                                    class="rep-btn-primary rep-coupon-box__apply"
                                    wire:click="applyCoupon"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="applyCoupon">اعمال</span>
                                <span wire:loading wire:target="applyCoupon">…</span>
                            </button>
                        @endif
                    </div>
                    @error('coupon_code') <p class="rep-wizard-error">{{ $message }}</p> @enderror
                    @if ($order->coupon)
                        <p class="rep-coupon-box__applied">
                            کد <strong>{{ $order->coupon->code }}</strong> اعمال شد
                            @if ($this->appliedCouponDiscount > 0)
                                — تخفیف {{ $this->formatMoney($this->appliedCouponDiscount) }}
                            @endif
                        </p>
                    @endif
                </div>
                <div class="rep-order-totals-card" data-rep-tour="rep-wizard-totals">
                    <div class="rep-order-totals-card__rows">
                        <div class="rep-order-totals-card__row">
                            <span>جمع اقلام</span>
                            <strong>{{ $this->formatMoney((int) $order->total_amount) }}</strong>
                        </div>
                        @if ($this->appliedCouponDiscount > 0)
                            <div class="rep-order-totals-card__row rep-order-totals-card__row--discount">
                                <span>تخفیف کد</span>
                                <strong>−{{ $this->formatMoney($this->appliedCouponDiscount) }}</strong>
                            </div>
                        @endif
                        @if ($order->freightCarrier)
                            <div class="rep-order-totals-card__row">
                                <span>باربری</span>
                                <strong>{{ $order->freightCarrier->displayLabel() }}</strong>
                            </div>
                        @endif
                        <div class="rep-order-totals-card__row">
                            <span>درگاه</span>
                            <strong>{{ \App\Support\ShopLabels::paymentMethod($order->payment_method) }}</strong>
                        </div>
                        <div class="rep-order-totals-card__row rep-order-totals-card__row--final">
                            <span>مبلغ نهایی</span>
                            <strong>{{ $this->formatMoney((int) $order->final_amount) }}</strong>
                        </div>
                    </div>
                </div>
                <div class="rep-review-cta" role="region" aria-label="اقدامات پیش‌فاکتور" data-rep-tour="rep-wizard-cta">
                    <p class="rep-review-cta__hint">
                        پس از تغییر تعداد، باربری یا درگاه، اول <strong>بروزرسانی</strong> را بزنید؛ سپس پیش‌فاکتور را ثبت کنید.
                    </p>
                    <button type="button"
                            class="rep-review-cta__add-product"
                            wire:click="goToStep('product')">
                        <span class="rep-review-cta__add-icon" aria-hidden="true">+</span>
                        <span>افزودن محصول دیگر</span>
                    </button>
                    <div class="rep-review-cta__actions">
                        <button type="button"
                                class="rep-review-cta__update"
                                wire:click="refreshReviewTotals"
                                wire:loading.attr="disabled"
                                wire:target="refreshReviewTotals,finishDraft">
                            <span wire:loading.remove wire:target="refreshReviewTotals">بروزرسانی مبالغ</span>
                            <span wire:loading wire:target="refreshReviewTotals">در حال محاسبه…</span>
                        </button>
                        <button type="button"
                                class="rep-review-cta__submit"
                                wire:click="finishDraft"
                                wire:loading.attr="disabled"
                                wire:target="finishDraft,refreshReviewTotals">
                            <span wire:loading.remove wire:target="finishDraft">ثبت پیش‌فاکتور</span>
                            <span wire:loading wire:target="finishDraft">در حال ثبت…</span>
                            <span class="rep-review-cta__submit-note" wire:loading.remove wire:target="finishDraft">بدون ویرایش بعدی</span>
                        </button>
                    </div>
                </div>
            @endif
            @error('order') <p class="rep-wizard-error">{{ $message }}</p> @enderror
            @if ($step === 'review' && $this->activeReservedProformas->isNotEmpty())
                <div class="rep-active-proformas" role="region" aria-label="پیش‌فاکتورهای با رزرو فعال">
                    <p class="rep-active-proformas__title">پیش‌فاکتورهای با رزرو فعال</p>
                    <p class="rep-active-proformas__hint">برای ثبت پیش‌فاکتور جدید، یکی را پرداخت کنید یا منتظر انقضای رزرو بمانید.</p>
                    <ul class="rep-active-proformas__list">
                        @foreach ($this->activeReservedProformas as $reservedOrder)
                            <li class="rep-active-proformas__item">
                                <a class="rep-active-proformas__link"
                                   href="{{ \App\Filament\Representative\Resources\DraftOrderResource::getUrl('view', ['record' => $reservedOrder->id], panel: 'representative') }}">
                                    <span class="rep-active-proformas__code">#{{ $reservedOrder->tracking_code }}</span>
                                    @if ($reservedOrder->user)
                                        <span class="rep-active-proformas__customer">{{ $reservedOrder->user->name }}</span>
                                    @endif
                                    <span class="rep-active-proformas__amount">{{ $this->formatMoney((int) $reservedOrder->final_amount) }}</span>
                                    @if ($reservedOrder->stock_reserved_until)
                                        <span class="rep-active-proformas__until">انقضای رزرو: {{ $reservedOrder->stock_reserved_until->shopJalali() }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @error('quantity') <p class="rep-wizard-error">{{ $message }}</p> @enderror
            </div>
        </section>
    @endif
    </div>

    @teleport('body')
        <div wire:loading.flex
             wire:target="openProductImages"
             class="rep-modal-backdrop rep-modal-backdrop--preparing"
             aria-live="polite"
             aria-busy="true">
            <div class="rep-modal rep-modal--compact" role="status">
                <div class="rep-modal__spinner" aria-hidden="true"></div>
                <p class="rep-modal__status">در حال آماده‌سازی تصاویر…</p>
                <p class="rep-modal__status rep-modal__status--hint">لطفاً چند لحظه صبر کنید.</p>
            </div>
        </div>
    @endteleport

    @if ($showProductImagesModal)
        @teleport('body')
            <div class="rep-modal-backdrop"
                 wire:click="closeProductImagesModal"
                 x-data
                 x-init="document.documentElement.classList.add('rep-modal-open')"
                 x-on:keydown.escape.window="$wire.closeProductImagesModal()">
                <div class="rep-modal rep-modal--gallery"
                     role="dialog"
                     aria-modal="true"
                     wire:click.stop
                     x-data="{ fullImageUrl: null }">
                    <div class="rep-modal__header">
                        <h3 class="rep-modal__title">{{ $productImagesModalTitle }}</h3>
                        <button type="button" class="rep-modal__close" wire:click="closeProductImagesModal" aria-label="بستن">×</button>
                    </div>
                    <div class="rep-modal__body">
                        <div class="rep-product-gallery">
                            @forelse ($productImagesModalItems as $index => $image)
                                <button type="button"
                                        class="rep-product-gallery__item rep-product-gallery__trigger"
                                        x-data="{ loaded: false, failed: false }"
                                        x-on:click="fullImageUrl = @js($image['full'])">
                                    <div class="rep-product-gallery__skeleton"
                                         x-bind:class="{ 'is-hidden': loaded || failed }"
                                         aria-hidden="true"></div>
                                    <img src="{{ $image['thumb'] }}"
                                         alt="{{ $productImagesModalTitle }}"
                                         loading="eager"
                                         decoding="async"
                                         width="160"
                                         height="160"
                                         class="rep-product-gallery__img"
                                         x-bind:class="{ 'is-loaded': loaded && !failed, 'is-failed': failed }"
                                         x-on:load="loaded = true"
                                         x-on:error="failed = true; loaded = true">
                                    <span class="rep-product-gallery__error" x-show="failed" x-cloak>بارگذاری نشد</span>
                                </button>
                            @empty
                                <p class="rep-wizard-empty">تصویری برای این محصول ثبت نشده است.</p>
                            @endforelse
                        </div>
                        @if (count($productImagesModalItems) > 0)
                            <p class="rep-modal__status rep-modal__status--hint">پیش‌نمایش سبک برای سرعت بیشتر است. برای کیفیت کامل روی تصویر کلیک کنید.</p>
                        @endif
                    </div>
                    <div class="rep-modal-fullimage"
                         x-show="fullImageUrl"
                         x-cloak
                         x-on:click.self="fullImageUrl = null"
                         x-on:keydown.escape.window="fullImageUrl = null">
                        <button type="button" class="rep-modal-fullimage__close" x-on:click="fullImageUrl = null" aria-label="بستن">×</button>
                        <img :src="fullImageUrl" alt="{{ $productImagesModalTitle }}" class="rep-modal-fullimage__img">
                    </div>
                </div>
            </div>
        @endteleport
    @endif
</div>
