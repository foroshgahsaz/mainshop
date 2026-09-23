@php
    $isMaintenance = ($variant ?? 'footer') === 'maintenance';
    $boxClass = $isMaintenance ? 'trust-badge' : 'site-footer__trust-badge';
    $innerClass = $isMaintenance ? 'trust-badge__inner' : 'site-footer__trust-badge-inner';
    $codeClass = $isMaintenance ? 'trust-badge__code' : 'site-footer__trust-badge-code';
    $linkClass = $isMaintenance ? 'trust-badge__link' : 'site-footer__trust-badge-link';
    $imageClass = $isMaintenance ? 'trust-badge__image' : 'site-footer__trust-badge-image';
@endphp

@foreach ($trustBadges as $badge)
    <div class="{{ $boxClass }} {{ $boxClass }}--{{ $badge['type'] }}"
         @if (filled($badge['title'])) title="{{ $badge['title'] }}" @endif>
        <div class="{{ $innerClass }}">
            @if ($badge['type'] === 'code')
                <div class="{{ $codeClass }}">
                    {!! $badge['code'] !!}
                </div>
            @elseif (filled($badge['image']))
                @php $imageUrl = \App\Support\ShopMedia::url($badge['image']); @endphp
                @if (filled($badge['link']))
                    <a href="{{ $badge['link'] }}"
                       class="{{ $linkClass }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       aria-label="{{ $badge['title'] }}">
                        <img src="{{ $imageUrl }}"
                             alt="{{ $badge['title'] }}"
                             class="{{ $imageClass }}"
                             loading="lazy">
                    </a>
                @else
                    <img src="{{ $imageUrl }}"
                         alt="{{ $badge['title'] }}"
                         class="{{ $imageClass }}"
                         loading="lazy">
                @endif
            @endif
        </div>
    </div>
@endforeach
