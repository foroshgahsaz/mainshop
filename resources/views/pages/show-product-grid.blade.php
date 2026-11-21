@extends('layouts.shop')

@section('title', \App\Support\CmsPageTitle::forPage($page))

@section('body_class', 'listing-page page-product-grid')

@section('meta')
    <x-seo-meta :seo="\App\Support\SeoPresenter::for($page, [
        'type' => 'website',
        'title' => \App\Support\CmsPageTitle::forPage($page),
        'url' => route('pages.show', $page),
    ])" />
@endsection

@section('content')
<div class="max-w-site mx-auto px-4 md:px-6 py-6 md:py-10 listing-page__inner">
    <nav class="listing-breadcrumb mb-4" aria-label="مسیر">
        <a href="{{ route('home') }}">صفحه اصلی</a>
        <span class="listing-breadcrumb__sep" aria-hidden="true">→</span>
        <span class="listing-breadcrumb__current">{{ $page->title }}</span>
    </nav>

    <div class="listing-page__intro mb-6">
        <h1 class="section-title">{{ $page->title }}</h1>
        @if(filled($page->content))
            <div class="prose prose-sm max-w-none text-gray-600 mt-3 rich-content">
                {!! $page->content !!}
            </div>
        @endif
    </div>

    @if($products->isEmpty())
        <div class="listing-empty">
            <h2 class="listing-empty__title">محصولی برای این صفحه تعریف نشده است</h2>
            <p class="listing-empty__text">در پنل ادمین از منوی صفحات، محصولات این کمپین را انتخاب کنید.</p>
            <a href="{{ route('products.index') }}" class="listing-empty__cta">مشاهده همه محصولات</a>
        </div>
    @else
        <p class="text-sm text-gray-500 mb-4">{{ number_format($products->count()) }} محصول</p>
        <div class="page-product-grid__cards grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-3 md:gap-4">
            @foreach($products as $product)
                <x-shop.product-card :product="$product" variant="clothing" section="page_product_grid" />
            @endforeach
        </div>
    @endif
</div>
@endsection