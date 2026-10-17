<x-filament-panels::page>
    <div class="product-price-stock-update" dir="rtl">
        <section class="admin-order__section product-price-stock-update__filters">
            <h2 class="admin-order__section-title">فیلترها</h2>
            <div class="product-price-stock-update__filter-grid">
                <div class="admin-order__field">
                    <label class="admin-order__label">دسته‌بندی</label>
                    <select wire:model.live="filterCategoryId" class="admin-order__select admin-order__select--full">
                        <option value="">همه دسته‌ها</option>
                        @foreach($this->categoryOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="admin-order__field">
                    <label class="admin-order__label">موجودی</label>
                    <select wire:model.live="filterStock" class="admin-order__select admin-order__select--full">
                        <option value="all">همه</option>
                        <option value="in_stock">موجود</option>
                        <option value="out_of_stock">ناموجود</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="admin-order__section product-price-stock-update__bulk">
            <h2 class="admin-order__section-title">تغییر گروهی (روی همهٔ محصولات فیلترشده)</h2>
            <p class="admin-order__hint">
                درصد مثبت = افزایش قیمت پایه (مثلاً ۱۰ یعنی +۱۰٪). درصد منفی = کاهش. تغییر موجودی به همه اضافه یا کم می‌شود (مثلاً ۱۰ یعنی +۱۰ عدد).
            </p>
            <div class="product-price-stock-update__bulk-grid">
                <div class="admin-order__field">
                    <label class="admin-order__label">درصد تغییر قیمت</label>
                    <input type="number" step="0.01" wire:model="bulkPricePercent" class="admin-order__input" dir="ltr" placeholder="مثلاً 10 یا -5">
                </div>
                <div class="admin-order__field">
                    <label class="admin-order__label">تغییر موجودی (عدد)</label>
                    <input type="number" step="1" wire:model="bulkStockDelta" class="admin-order__input" dir="ltr" placeholder="مثلاً 10 یا -2">
                </div>
                <div class="product-price-stock-update__bulk-action">
                    <x-filament::button
                        color="warning"
                        icon="heroicon-o-adjustments-horizontal"
                        wire:click="applyBulkAdjustments"
                        wire:loading.attr="disabled"
                        wire:confirm="تغییر گروهی روی تمام محصولات مطابق فیلتر اعمال می‌شود. ادامه می‌دهید؟"
                    >
                        اعمال گروهی
                    </x-filament::button>
                </div>
            </div>
        </section>

        <section class="admin-order__section">
            <h2 class="admin-order__section-title">لیست محصولات</h2>
            <p class="admin-order__hint">
                {{ number_format($this->products->total()) }} محصول — قیمت‌ها به <strong>تومان</strong> (فیلد قیمت پایه).
            </p>

            <div class="admin-payment-log-viewer__table-wrap">
                <table class="admin-payment-log-viewer__table product-price-stock-update__table">
                    <thead>
                        <tr>
                            <th>شناسه</th>
                            <th>نام</th>
                            <th>دسته</th>
                            <th>قیمت (تومان)</th>
                            <th>موجودی</th>
                            <th>آخرین ویرایش</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->products as $product)
                            <tr wire:key="product-row-{{ $product->id }}">
                                <td dir="ltr">{{ $product->id }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category?->name ?? '—' }}</td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        class="admin-order__input product-price-stock-update__num"
                                        dir="ltr"
                                        wire:model="rowPrices.{{ $product->id }}"
                                    >
                                </td>
                                <td>
                                    <input
                                        type="number"
                                        min="0"
                                        class="admin-order__input product-price-stock-update__num"
                                        dir="ltr"
                                        wire:model="rowStocks.{{ $product->id }}"
                                    >
                                </td>
                                <td class="product-price-stock-update__meta">
                                    <div>{{ $product->updated_at?->shopJalali('Y/m/d H:i') ?? '—' }}</div>
                                    @if($product->lastEditedBy)
                                        <div class="product-price-stock-update__editor">{{ $product->lastEditedBy->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <x-filament::button
                                        size="sm"
                                        wire:click="saveProduct({{ $product->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="saveProduct({{ $product->id }})"
                                    >
                                        ذخیره
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-500 py-6">محصولی با این فیلتر یافت نشد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($this->products->hasPages())
                <div class="product-price-stock-update__pagination mt-4">
                    {{ $this->products->links() }}
                </div>
            @endif
        </section>
    </div>

    @once
        @push('styles')
            <style>
                .product-price-stock-update__filter-grid,
                .product-price-stock-update__bulk-grid {
                    display: grid;
                    gap: 1rem;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    align-items: end;
                }
                .product-price-stock-update__bulk-action {
                    display: flex;
                    align-items: flex-end;
                    padding-bottom: 2px;
                }
                .product-price-stock-update__num {
                    min-width: 7rem;
                    max-width: 10rem;
                }
                .product-price-stock-update__meta {
                    font-size: 0.8rem;
                    white-space: nowrap;
                }
                .product-price-stock-update__editor {
                    color: #7e8299;
                    font-size: 0.75rem;
                }
                .product-price-stock-update__table input.admin-order__input {
                    padding: 0.35rem 0.5rem;
                    font-size: 0.875rem;
                }
            </style>
        @endpush
    @endonce
</x-filament-panels::page>
