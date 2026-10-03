<?php

namespace App\Filament\Support;

use App\Filament\Resources\Pages\AdminListRecords;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Str;

class AdminTopBarCreateResolver
{
    /**
     * @return array{url: string, label: string}|null
     */
    public static function resolve(?string $requestPath = null): ?array
    {
        $panel = Filament::getCurrentPanel();
        if ($panel?->getId() !== 'admin') {
            return null;
        }

        $pageClass = self::resolveListPageClass($requestPath);
        if ($pageClass === null) {
            return null;
        }

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
     * @return class-string|null
     */
    protected static function resolveListPageClass(?string $requestPath = null): ?string
    {
        $panel = Filament::getCurrentPanel();
        if ($panel === null) {
            return null;
        }

        $path = $requestPath ?? '/'.ltrim(request()->path(), '/');
        $path = rtrim($path, '/') ?: '/';

        foreach ($panel->getResources() as $resourceClass) {
            if (! $resourceClass::hasPage('index')) {
                continue;
            }

            $indexPath = parse_url($resourceClass::getUrl('index'), PHP_URL_PATH);
            if (! is_string($indexPath) || $indexPath === '') {
                continue;
            }

            $indexPath = rtrim($indexPath, '/') ?: '/';

            if ($path !== $indexPath) {
                continue;
            }

            $registration = $resourceClass::getPages()['index'] ?? null;
            $pageClass = $registration?->getPage();

            return is_string($pageClass) ? $pageClass : null;
        }

        return null;
    }
}
