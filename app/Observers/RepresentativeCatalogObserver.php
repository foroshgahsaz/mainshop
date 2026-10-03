<?php

namespace App\Observers;

use App\Services\Representative\RepresentativeCatalogCache;

class RepresentativeCatalogObserver
{
    public function __construct(
        protected RepresentativeCatalogCache $catalogCache,
    ) {}

    public function saved(object $model): void
    {
        $this->catalogCache->flush();
    }

    public function deleted(object $model): void
    {
        $this->catalogCache->flush();
    }
}
