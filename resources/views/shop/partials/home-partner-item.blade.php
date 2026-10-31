@if (! empty($partner['link']))
    <a href="{{ $partner['link'] }}" class="home-partners-card" target="_blank" rel="noopener noreferrer">
        @include('shop.partials.home-partner-logo', ['partner' => $partner])
    </a>
@else
    <div class="home-partners-card home-partners-card--static">
        @include('shop.partials.home-partner-logo', ['partner' => $partner])
    </div>
@endif
