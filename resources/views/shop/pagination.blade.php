@if ($paginator->hasPages())
    <nav role="navigation" aria-label="صفحه‌بندی" class="shop-pagination">
        <div class="shop-pagination__mobile">
            @if ($paginator->onFirstPage())
                <span class="shop-pagination__btn shop-pagination__btn--disabled">قبلی</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="shop-pagination__btn">قبلی</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="shop-pagination__btn">بعدی</a>
            @else
                <span class="shop-pagination__btn shop-pagination__btn--disabled">بعدی</span>
            @endif
        </div>

        <div class="shop-pagination__desktop">
            @if ($paginator->onFirstPage())
                <span class="shop-pagination__nav shop-pagination__nav--disabled" aria-hidden="true">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="shop-pagination__nav" aria-label="صفحه قبلی">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="shop-pagination__ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="shop-pagination__page shop-pagination__page--active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="shop-pagination__page">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="shop-pagination__nav" aria-label="صفحه بعدی">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                </a>
            @else
                <span class="shop-pagination__nav shop-pagination__nav--disabled" aria-hidden="true">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
