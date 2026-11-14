@php
    /** @var array{id:int,name:string,phone:?string,province:?string,city:?string,url:?string,customers_count?:int,role?:string} $node */
    $role = $node['role'] ?? 'representative';
    $initials = collect(preg_split('/\s+/u', trim((string) ($node['name'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [])
        ->take(2)
        ->map(fn (string $part) => mb_substr($part, 0, 1))
        ->implode('');
    if ($initials === '') {
        $initials = '؟';
    }
    $chips = collect([
        $node['phone'] ?? null,
        $node['province'] ?? null,
        $node['city'] ?? null,
    ])->filter()->values();
    $customersCount = (int) ($node['customers_count'] ?? 0);
@endphp

<article class="bst-card bst-card--{{ $role }}">
    <div class="bst-card__avatar" aria-hidden="true">{{ $initials }}</div>
    <div class="bst-card__body">
        <div class="bst-card__top">
            <span class="bst-card__role">{{ $roleLabel }}</span>
            @if ($customersCount > 0)
                <span class="bst-card__badge">{{ number_format($customersCount) }} مشتری</span>
            @endif
        </div>
        @if (! empty($node['url']))
            <a href="{{ $node['url'] }}" class="bst-card__name">{{ $node['name'] }}</a>
        @else
            <span class="bst-card__name">{{ $node['name'] }}</span>
        @endif
        @if ($chips->isNotEmpty())
            <ul class="bst-card__chips">
                @foreach ($chips as $chip)
                    <li>{{ $chip }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</article>
