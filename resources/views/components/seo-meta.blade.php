@props(['seo'])

@php
    /** @var \App\Support\SeoPresenter $seo */
@endphp

<meta name="description" content="{{ $seo->description() }}">
<meta name="robots" content="{{ $seo->robots() }}">
<link rel="canonical" href="{{ $seo->url() }}">

<meta property="og:type" content="{{ $seo->type() }}">
<meta property="og:title" content="{{ $seo->ogTitle() }}">
<meta property="og:description" content="{{ $seo->ogDescription() }}">
<meta property="og:url" content="{{ $seo->url() }}">
<meta property="og:locale" content="fa_IR">
@if($seo->image())
    <meta property="og:image" content="{{ $seo->image() }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo->ogTitle() }}">
<meta name="twitter:description" content="{{ $seo->ogDescription() }}">
@if($seo->image())
    <meta name="twitter:image" content="{{ $seo->image() }}">
@endif
