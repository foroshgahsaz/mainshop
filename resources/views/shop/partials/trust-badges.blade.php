@php
    $trustBadges = app(\App\Services\Settings\TrustBadgeService::class)->active();
@endphp

@if ($trustBadges !== [])
    <div class="site-footer__trust-col">
        <h4 class="footer-heading hidden md:block">نمادهای اعتماد</h4>
        <div class="site-footer__trust-badges">
            @foreach ($trustBadges as $badge)
                <div class="site-footer__trust-badge site-footer__trust-badge--{{ $badge['type'] }}"
                     @if (filled($badge['title'])) title="{{ $badge['title'] }}" @endif>
                    <div class="site-footer__trust-badge-inner">
                        @if ($badge['type'] === 'code')
                            <div class="site-footer__trust-badge-code">
                                {!! $badge['code'] !!}
                            </div>
                        @elseif (filled($badge['image']))
                            @php $imageUrl = \App\Support\ShopMedia::url($badge['image']); @endphp
                            @if (filled($badge['link']))
                                <a href="{{ $badge['link'] }}"
                                   class="site-footer__trust-badge-link"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   aria-label="{{ $badge['title'] }}">
                                    <img src="{{ $imageUrl }}"
                                         alt="{{ $badge['title'] }}"
                                         class="site-footer__trust-badge-image"
                                         loading="lazy">
                                </a>
                            @else
                                <img src="{{ $imageUrl }}"
                                     alt="{{ $badge['title'] }}"
                                     class="site-footer__trust-badge-image"
                                     loading="lazy">
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
