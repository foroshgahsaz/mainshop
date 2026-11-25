<x-filament-panels::page>
    <div class="admin-order" dir="rtl">
        <section class="admin-order__box" style="margin-bottom:1.25rem;">
            <h2 class="admin-order__box-title">جستجوی سراسری SKU</h2>
            <p style="font-size:0.875rem;color:var(--gray-600);margin-bottom:1rem;">
                محصولات <strong>حذف‌شده</strong>، غیرفعال، و SKU روی <strong>تنوع (variant)</strong> هم جستجو می‌شوند — حتی اگر در لیست عادی دیده نشوند.
            </p>
            <form wire:submit="search" class="flex flex-wrap gap-3 items-end">
                <div style="flex:1;min-width:14rem;">
                    <label class="admin-order__label">کد کالا / SKU</label>
                    <input
                        type="text"
                        wire:model="sku"
                        class="admin-order__input"
                        dir="ltr"
                        placeholder="01040102906014"
                        autocomplete="off"
                    >
                    @error('sku')
                        <p class="text-sm text-danger-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="admin-order__btn admin-order__btn--primary">
                    جستجو
                </button>
            </form>
        </section>

        @if($searched)
            @if($results === [])
                <section class="admin-order__box">
                    <p>نتیجه‌ای یافت نشد. فاصله یا صفرهای ابتدای کد را بررسی کنید؛ جستجوی جزئی فقط وقتی اجرا می‌شود که تطابق دقیق نباشد.</p>
                </section>
            @else
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="p-2">نوع</th>
                                <th class="p-2">ID</th>
                                <th class="p-2">SKU</th>
                                <th class="p-2">نام</th>
                                <th class="p-2">برند</th>
                                <th class="p-2">دسته</th>
                                <th class="p-2">بنکدار</th>
                                <th class="p-2">وضعیت</th>
                                <th class="p-2">دسترسی شما</th>
                                <th class="p-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $row)
                                <tr class="border-t border-gray-100 dark:border-gray-700">
                                    <td class="p-2">{{ $row['entity'] === 'variant' ? 'تنوع' : 'محصول' }}</td>
                                    <td class="p-2 font-mono" dir="ltr">{{ $row['entity_id'] }}</td>
                                    <td class="p-2 font-mono" dir="ltr">{{ $row['sku'] }}</td>
                                    <td class="p-2">{{ $row['product_name'] }}</td>
                                    <td class="p-2">{{ $row['brand'] }}</td>
                                    <td class="p-2">{{ $row['category'] }}</td>
                                    <td class="p-2">{{ $row['bankdar_label'] }}</td>
                                    <td class="p-2">
                                        @if($row['deleted'])
                                            <span class="text-danger-600">حذف‌شده {{ $row['deleted_at'] ? '('.$row['deleted_at'].')' : '' }}</span>
                                        @elseif(! $row['is_active'])
                                            غیرفعال
                                        @else
                                            فعال
                                        @endif
                                        @if($row['match_type'] === 'partial')
                                            <span class="text-gray-500 text-xs">(تطابق جزئی)</span>
                                        @endif
                                    </td>
                                    <td class="p-2">
                                        @if($row['in_actor_catalog'] === null)
                                            —
                                        @elseif($row['in_actor_catalog'])
                                            در کاتالوگ شما
                                        @else
                                            <span class="text-warning-600">خارج از کاتالوگ شما</span>
                                        @endif
                                    </td>
                                    <td class="p-2">
                                        @if($this->canEditProduct($row['product_id']))
                                            <a href="{{ $this->editUrl($row['product_id']) }}" class="text-primary-600 hover:underline">ویرایش</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>