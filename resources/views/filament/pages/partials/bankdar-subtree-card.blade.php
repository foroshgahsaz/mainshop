@php
    /** @var array{id:int,name:string,phone:?string,province:?string,city:?string,url:?string,customers_count?:int} $node */
    $meta = collect([
        $node['phone'] ?? null,
        $node['province'] ?? null,
        $node['city'] ?? null,
        isset($node['customers_count']) && (int) $node['customers_count'] > 0
            ? ((int) $node['customers_count']).' مشتری'
            : null,
    ])->filter()->implode(' · ');
@endphp

<div class="bankdar-subtree__card">
    <span class="bankdar-subtree__role">{{ $roleLabel }}</span>
    @if (! empty($node['url']))
        <a href="{{ $node['url'] }}" class="bankdar-subtree__name"><strong>{{ $node['name'] }}</strong></a>
    @else
        <strong class="bankdar-subtree__name">{{ $node['name'] }}</strong>
    @endif
    @if ($meta !== '')
        <p class="bankdar-subtree__meta">{{ $meta }}</p>
    @endif
</div>
