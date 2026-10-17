@php
    /** @var array{family_id: int, family_name: string, brands: list<array{name: string, url: string}>} $popup */
@endphp

<div id="chineseServiceBrandsModal"
     class="chinese-brands-modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="chineseServiceBrandsModalTitle"
     aria-hidden="true">
    <div class="chinese-brands-modal__panel">
        <div class="chinese-brands-modal__header">
            <h3 id="chineseServiceBrandsModalTitle" class="chinese-brands-modal__title">{{ $popup['family_name'] }}</h3>
            <button type="button"
                    class="chinese-brands-modal__close"
                    onclick="toggleChineseServiceBrandsModal(false)"
                    aria-label="بستن">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
        <p class="chinese-brands-modal__hint">برند مورد نظر را انتخاب کنید</p>
        <ul class="chinese-brands-modal__list">
            @foreach ($popup['brands'] as $index => $brand)
                <li>
                    <a href="{{ $brand['url'] }}"
                       class="chinese-brands-modal__item"
                       onclick="toggleChineseServiceBrandsModal(false)">
                        <span class="chinese-brands-modal__index">{{ $index + 1 }}</span>
                        <span class="chinese-brands-modal__name">{{ $brand['name'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
