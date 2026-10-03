<?php

namespace App\Filament\Support;

use App\Filament\Resources\Pages\AdminListRecords;
use Filament\Resources\Resource;

class AdminTopBarCreateResolver
{
    /**
     * @param  class-string  $pageClass
     * @return array{url: string, label: string}|null
     */
    public static function resolveForPage(string $pageClass): ?array
    {
        if (! is_subclass_of($pageClass, AdminListRecords::class)) {
            return null;
        }

        if (! $pageClass::canShowTopBarCreate()) {
            return null;
        }

        /** @var class-string<Resource> $resourceClass */
        $resourceClass = $pageClass::getResource();

        if (! $resourceClass::canCreate() || ! $resourceClass::hasPage('create')) {
            return null;
        }

        $label = $pageClass::topBarCreateLabel() ?? AdminListHeader::createTitle($resourceClass);

        return [
            'url' => $resourceClass::getUrl('create'),
            'label' => $label,
        ];
    }

    /**
     * @deprecated Use resolveForPage() from list mount or View::shared()
     *
     * @return array{url: string, label: string}|null
     */
    public static function resolve(?string $requestPath = null): ?array
    {
        return view()->shared('adminTopBarCreate');
    }
}
