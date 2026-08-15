@props(['content' => ''])

@if(filled($content))
    <div {{ $attributes->merge(['class' => 'rich-content prose prose-sm max-w-none']) }}>
        {!! $content !!}
    </div>
@endif
