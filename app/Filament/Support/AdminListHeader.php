<?php

namespace App\Filament\Support;

use App\Filament\Resources\AttributeResource;
use App\Filament\Resources\BrandResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CouponResource;
use App\Filament\Resources\FreightCarrierResource;
use App\Filament\Resources\HomeSliderResource;
use App\Filament\Resources\MediaFileResource;
use App\Filament\Resources\MenuItemResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PostResource;
use App\Filament\Resources\ProductFamilyResource;
use App\Filament\Resources\ProductPlantResource;
use App\Filament\Resources\ProductQuestionResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductReviewResource;
use App\Filament\Resources\ProductTemplateResource;
use App\Filament\Resources\ShippingMethodResource;
use App\Filament\Resources\UserResource;
use Filament\Resources\Resource;

class AdminListHeader
{
    /** @param  class-string<resource>  $resourceClass */
    public static function icon(string $resourceClass): string
    {
        return match ($resourceClass) {
            UserResource::class => 'fa-users',
            ProductResource::class => 'fa-box-open',
            CategoryResource::class => 'fa-folder',
            OrderResource::class => 'fa-shopping-bag',
            PaymentResource::class => 'fa-credit-card',
            BrandResource::class => 'fa-certificate',
            CouponResource::class => 'fa-percent',
            PostResource::class => 'fa-newspaper',
            PageResource::class => 'fa-file-alt',
            HomeSliderResource::class => 'fa-images',
            MenuItemResource::class => 'fa-bars',
            MediaFileResource::class => 'fa-photo-film',
            AttributeResource::class => 'fa-tags',
            ProductFamilyResource::class => 'fa-layer-group',
            ProductTemplateResource::class => 'fa-clone',
            ProductPlantResource::class => 'fa-industry',
            ShippingMethodResource::class => 'fa-truck',
            FreightCarrierResource::class => 'fa-shipping-fast',
            ProductQuestionResource::class => 'fa-question-circle',
            ProductReviewResource::class => 'fa-star',
            default => 'fa-list',
        };
    }

    /** @param  class-string<resource>  $resourceClass */
    public static function sectionLabel(string $resourceClass): string
    {
        $label = $resourceClass::getNavigationLabel();

        return $label !== '' ? $label : $resourceClass::getModelLabel();
    }

    /** @param  class-string<resource>  $resourceClass */
    public static function listTitle(string $resourceClass): string
    {
        return 'لیست '.$resourceClass::getPluralModelLabel();
    }

    /** @param  class-string<resource>  $resourceClass */
    public static function createTitle(string $resourceClass): string
    {
        return 'افزودن '.$resourceClass::getModelLabel();
    }

    /** @param  class-string<resource>  $resourceClass */
    public static function editTitle(string $resourceClass): string
    {
        return 'ویرایش '.$resourceClass::getModelLabel();
    }
}
