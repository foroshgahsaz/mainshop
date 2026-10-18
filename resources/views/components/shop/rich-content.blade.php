@props(['content' => ''])

@php
    use App\Support\RichContentPresenter;

    $split = RichContentPresenter::splitVideos($content);
@endphp

@if(filled($split['videos']) || filled($split['body']))
    <div {{ $attributes->class(['rich-content-shell']) }}>
        @if(filled($split['videos']))
            <div class="rich-content rich-content-videos">
                {!! $split['videos'] !!}
            </div>
        @endif
        @if(filled($split['body']))
            <div @class([
                'rich-content prose prose-sm max-w-none',
                'rich-content-body',
                'mt-4' => filled($split['videos']),
            ])>
                {!! $split['body'] !!}
            </div>
        @endif
    </div>
@endif
