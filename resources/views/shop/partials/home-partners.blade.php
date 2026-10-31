@php
    $partners = $homepage_partners ?? [];
@endphp

@if ($partners !== [])
    <section class="home-partners-section" aria-label="همکاران ما">
        <div class="max-w-site mx-auto px-4 py-8 md:py-10">
            <div class="section-header mb-6">
                <h2 class="section-title">همکاران ما</h2>
            </div>
            <div class="home-partners-grid home-partners-grid--desktop">
                @foreach ($partners as $partner)
                    @include('shop.partials.home-partner-item', ['partner' => $partner])
                @endforeach
            </div>
            <div class="swiper homePartnersSwiper home-partners-swiper" aria-label="اسلایدر همکاران">
                <div class="swiper-wrapper">
                    @foreach ($partners as $partner)
                        <div class="swiper-slide">
                            @include('shop.partials.home-partner-item', ['partner' => $partner])
                        </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>
@endif
