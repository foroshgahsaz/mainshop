@if (! empty($partner['logo']))
    <img src="{{ $partner['logo'] }}" alt="{{ $partner['name'] }}" class="home-partners-card__logo" loading="lazy">
@else
    <span class="home-partners-card__fallback">{{ mb_substr($partner['name'], 0, 1) }}</span>
@endif
