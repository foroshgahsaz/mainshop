@php
    use App\Support\ProductSpecRows;

    $specRows = ProductSpecRows::for($product);
    $defaultTab = filled($product->description) ? 'description' : ($specRows !== [] ? 'specs' : 'reviews');
@endphp

<section id="product-details" class="product-details mb-10 md:mb-14">
    <div class="product-tabs" role="tablist" aria-label="جزئیات محصول">
        <button type="button"
                class="product-tab @if($defaultTab === 'description') is-active @endif"
                role="tab"
                id="product-tab-description"
                aria-selected="{{ $defaultTab === 'description' ? 'true' : 'false' }}"
                aria-controls="product-panel-description"
                data-product-tab="description">
            <svg class="product-tab__icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
            </svg>
            <span>توضیحات</span>
        </button>

        <button type="button"
                class="product-tab @if($defaultTab === 'reviews') is-active @endif"
                role="tab"
                id="product-tab-reviews"
                aria-selected="{{ $defaultTab === 'reviews' ? 'true' : 'false' }}"
                aria-controls="product-panel-reviews"
                data-product-tab="reviews">
            <svg class="product-tab__icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
            </svg>
            <span>نظرات</span>
            @if ($reviewCount > 0)
                <span class="product-tab__badge">{{ number_format($reviewCount) }}</span>
            @else
                <span class="product-tab__badge product-tab__badge--muted">0</span>
            @endif
        </button>

        <button type="button"
                class="product-tab @if($defaultTab === 'qa') is-active @endif"
                role="tab"
                id="product-tab-qa"
                aria-selected="{{ $defaultTab === 'qa' ? 'true' : 'false' }}"
                aria-controls="product-panel-qa"
                data-product-tab="qa">
            <svg class="product-tab__icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/>
            </svg>
            <span>پرسش و پاسخ</span>
            @if (($questionCount ?? 0) > 0)
                <span class="product-tab__badge">{{ number_format($questionCount) }}</span>
            @else
                <span class="product-tab__badge product-tab__badge--muted">0</span>
            @endif
        </button>

        <button type="button"
                class="product-tab @if($defaultTab === 'specs') is-active @endif"
                role="tab"
                id="product-tab-specs"
                aria-selected="{{ $defaultTab === 'specs' ? 'true' : 'false' }}"
                aria-controls="product-panel-specs"
                data-product-tab="specs">
            <svg class="product-tab__icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
            <span>مشخصات</span>
        </button>
    </div>

    <div class="product-tab-panels">
        <div class="product-tab-panel @if($defaultTab === 'description') is-active @endif"
             role="tabpanel"
             id="product-panel-description"
             aria-labelledby="product-tab-description"
             data-product-panel="description"
             @if($defaultTab !== 'description') hidden @endif>
            @if(filled($product->description))
                <x-shop.rich-content :content="$product->description" class="product-tab-content" />
            @else
                <div class="product-tab-empty">
                    <p>توضیحاتی برای این محصول ثبت نشده است.</p>
                </div>
            @endif
        </div>

        <div class="product-tab-panel @if($defaultTab === 'reviews') is-active @endif"
             role="tabpanel"
             id="product-panel-reviews"
             aria-labelledby="product-tab-reviews"
             data-product-panel="reviews"
             @if($defaultTab !== 'reviews') hidden @endif>
            <div id="reviews">
                <livewire:product.reviews :product="$product" :compact="true" lazy :key="'reviews-'.$product->id" />
            </div>
        </div>

        <div class="product-tab-panel @if($defaultTab === 'qa') is-active @endif"
             role="tabpanel"
             id="product-panel-qa"
             aria-labelledby="product-tab-qa"
             data-product-panel="qa"
             @if($defaultTab !== 'qa') hidden @endif>
            <livewire:product.questions :product="$product" lazy :key="'questions-'.$product->id" />
        </div>

        <div class="product-tab-panel @if($defaultTab === 'specs') is-active @endif"
             role="tabpanel"
             id="product-panel-specs"
             aria-labelledby="product-tab-specs"
             data-product-panel="specs"
             @if($defaultTab !== 'specs') hidden @endif>
            @if($specRows !== [])
                <table class="product-spec-table">
                    <tbody>
                        @foreach($specRows as $row)
                            <tr>
                                <th scope="row">{{ $row['label'] }}</th>
                                <td>{{ $row['value'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="product-tab-empty">
                    <p>مشخصات فنی برای این محصول ثبت نشده است.</p>
                </div>
            @endif
        </div>
    </div>
</section>
