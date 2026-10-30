@php
    $partners = $homepage_partners ?? [];
@endphp

@if ($partners !== [])
    <section class="home-partners-section" aria-label="همکاران ما">
        <div class="max-w-site mx-auto px-4 py-8 md:py-10">
            <div class="section-header mb-6">
                <h2 class="section-title">همکاران ما</h2>
            </div>
            <div class="home-partners-grid">
                @foreach ($partners as $partner)
                    @if (! empty($partner['link']))
                        <a href="{{ $partner['link'] }}" class="home-partners-card" target="_blank" rel="noopener noreferrer">
                            @include('shop.partials.home-partner-logo', ['partner' => $partner])
                        </a>
                    @else
                        <div class="home-partners-card home-partners-card--static">
                            @include('shop.partials.home-partner-logo', ['partner' => $partner])
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
@endif
