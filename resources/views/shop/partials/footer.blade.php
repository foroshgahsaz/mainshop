@php
    $footer = app(\App\Services\Settings\FooterSettingsService::class)->all();
    $footerTel = app(\App\Services\Settings\FooterSettingsService::class);
    $copyright = $footer['copyright'] ?: '© ۱۴۰۵ '.site_name().' — تمامی حقوق این وب‌سایت محفوظ است.';
    $featureIcons = [
        '<path d="M13 16V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1m8-1 1 0 0 1-1 1H9m4-1V8a1 1 0 0 1 1-1h2.586a1 1 0 0 1 .707.293l3.414 3.414a1 1 0 0 1 .293.707V16a1 1 0 0 1-1 1h-1m-6-1a1 1 0 0 0 1 1h1M5 17a2 2 0 1 0 4 0m10 0a2 2 0 1 0 4 0" />',
        '<path d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />',
        '<path d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />',
        '<path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" />',
    ];
    $socialLinks = array_filter([
        'instagram' => $footer['instagram'] ?? '',
        'telegram' => $footer['telegram'] ?? '',
        'whatsapp' => $footer['whatsapp'] ?? '',
        'linkedin' => $footer['linkedin'] ?? '',
    ]);
@endphp

  <!-- FOOTER -->
  <footer class="site-footer">
    <!-- Features -->
    @if(collect($footer['features'] ?? [])->contains(fn (array $feature): bool => ($feature['enabled'] ?? true) && filled($feature['title'] ?? null)))
      <div class="site-footer__features">
        <div class="max-w-site mx-auto px-4 py-2 md:py-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 md:gap-4">
            @foreach($footer['features'] as $index => $feature)
              @continue(! ($feature['enabled'] ?? true) || blank($feature['title'] ?? null))
              <div class="site-footer__feature">
                <span class="site-footer__feature-icon"><svg class="w-5 h-5"
                       fill="none"
                       stroke="currentColor"
                       stroke-width="1.8"
                       viewBox="0 0 24 24">
                    {!! $featureIcons[$index % count($featureIcons)] !!}
                  </svg></span>
                <div>
                  <p class="text-sm font-bold">{{ $feature['title'] }}</p>
                  @if(filled($feature['subtitle'] ?? null))
                    <p class="text-xs text-white/70 mt-0.5">{{ $feature['subtitle'] }}</p>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif

    <!-- Main links -->
    <div class="max-w-site mx-auto px-4 py-8 md:py-12">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-0 md:gap-8 lg:gap-10">
        <!-- Brand -->
        <div class="lg:col-span-4 pb-6 md:pb-0 border-b md:border-b-0 border-gray-100">
          <a href="{{ route('home') }}"
             class="inline-flex items-center gap-2.5">
            <span class="text-2xl font-black text-navy">{{ site_name() }}</span>
          </a>
          @if(filled($footer['brand_description'] ?? null))
            <p class="text-sm text-gray-500 leading-7 mt-4">{{ $footer['brand_description'] }}</p>
          @endif
          <div class="site-footer__brand-contact mt-4 space-y-2.5">
            @if(filled($footer['phone'] ?? null))
              <div class="site-footer__contact-item">
                <svg class="w-4 h-4"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     viewBox="0 0 24 24">
                  <path
                        d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                </svg>
                <span>تلفن: <a href="{{ $footerTel->telHref($footer['phone']) }}"
                     class="hover:text-brand-green transition-colors"
                     dir="ltr">{{ $footer['phone'] }}</a></span>
              </div>
            @endif
            @if(filled($footer['mobile'] ?? null))
              <div class="site-footer__contact-item">
                <svg class="w-4 h-4"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     viewBox="0 0 24 24">
                  <path
                        d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                </svg>
                <span>موبایل: <a href="{{ $footerTel->telHref($footer['mobile']) }}"
                     class="hover:text-brand-green transition-colors"
                     dir="ltr">{{ $footer['mobile'] }}</a></span>
              </div>
            @endif
            @if(filled($footer['address'] ?? null))
              <div class="site-footer__contact-item">
                <svg class="w-4 h-4"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8"
                     viewBox="0 0 24 24">
                  <path
                        d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  <path
                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                </svg>
                <span>آدرس: {{ $footer['address'] }}</span>
              </div>
            @endif
          </div>
          @if($socialLinks !== [])
            <div class="site-footer__social mt-5">
              @if(filled($socialLinks['instagram'] ?? null))
                <a href="{{ $socialLinks['instagram'] }}"
                   class="site-footer__social-link"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="اینستاگرام"><svg class="w-4 h-4"
                       fill="currentColor"
                       viewBox="0 0 24 24">
                    <path
                          d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z" />
                  </svg></a>
              @endif
              @if(filled($socialLinks['telegram'] ?? null))
                <a href="{{ $socialLinks['telegram'] }}"
                   class="site-footer__social-link"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="تلگرام"><svg class="w-4 h-4"
                       fill="currentColor"
                       viewBox="0 0 24 24">
                    <path
                          d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                  </svg></a>
              @endif
              @if(filled($socialLinks['whatsapp'] ?? null))
                <a href="{{ $socialLinks['whatsapp'] }}"
                   class="site-footer__social-link"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="واتساپ"><svg class="w-4 h-4"
                       fill="currentColor"
                       viewBox="0 0 24 24">
                    <path
                          d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.883 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z" />
                  </svg></a>
              @endif
              @if(filled($socialLinks['linkedin'] ?? null))
                <a href="{{ $socialLinks['linkedin'] }}"
                   class="site-footer__social-link"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="لینکدین"><svg class="w-4 h-4"
                       fill="currentColor"
                       viewBox="0 0 24 24">
                    <path
                          d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                  </svg></a>
              @endif
            </div>
          @endif
        </div>

        <!-- Links columns -->
        <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-0 sm:gap-6">
          @if(collect($footer['quick_links'] ?? [])->contains(fn (array $link): bool => ($link['enabled'] ?? true) && filled($link['label'] ?? null)))
            <details class="site-footer__accordion md:block"
                     open>
              <summary>{{ $footer['quick_links_title'] ?? 'دسترسی سریع' }}<span class="site-footer__accordion-chevron text-gray-400"><svg class="w-4 h-4"
                       fill="none"
                       stroke="currentColor"
                       stroke-width="2"
                       viewBox="0 0 24 24">
                    <path d="m6 9 6 6 6-6" />
                  </svg></span></summary>
              <div class="site-footer__accordion-body">
                <ul class="space-y-1">
                  @foreach($footer['quick_links'] as $link)
                    @continue(! ($link['enabled'] ?? true) || blank($link['label'] ?? null) || blank($link['url'] ?? null))
                    <li><a href="{{ $link['url'] }}"
                         class="footer-link">{{ $link['label'] }}</a></li>
                  @endforeach
                </ul>
              </div>
            </details>
          @endif

          @include('shop.partials.trust-badges')
        </div>
      </div>

      @if(collect($footer['about_paragraphs'] ?? [])->filter()->isNotEmpty())
        <div class="site-footer__about">
          @foreach($footer['about_paragraphs'] as $paragraph)
            @if(filled($paragraph))
              <p>{{ $paragraph }}</p>
            @endif
          @endforeach
        </div>
      @endif
    </div>

    <!-- Bottom bar -->
    <div class="site-footer__bottom">
      <div class="max-w-site mx-auto px-4 py-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <p class="text-xs leading-6 text-center sm:text-right">{{ $copyright }}</p>
        <div class="flex items-center justify-center sm:justify-end gap-4 text-xs">
          @foreach($footer['bottom_links'] ?? [] as $link)
            @continue(! ($link['enabled'] ?? true) || blank($link['label'] ?? null) || blank($link['url'] ?? null))
            <a href="{{ $link['url'] }}"
               class="hover:text-white transition-colors">{{ $link['label'] }}</a>
          @endforeach
          <a href="#"
             onclick="window.scrollTo({top:0,behavior:'smooth'});return false;"
             class="inline-flex items-center gap-1.5 hover:text-white transition-colors"
             aria-label="بازگشت به بالا">
            <span>بازگشت به بالا</span>
            <svg class="w-3.5 h-3.5"
                 fill="none"
                 stroke="currentColor"
                 stroke-width="2"
                 viewBox="0 0 24 24">
              <path d="m18 15-6-6-6 6" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </footer>
