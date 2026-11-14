<?php

namespace App\Support;

use App\Services\Shop\SoftDeletesSchemaRepair;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * مثل SoftDeletingScope؛ اگر ستون deleted_at در DB نباشد، شرط را اعمال نمی‌کند (جلوگیری از ۵۰۰).
 */
class ConditionalSoftDeletingScope extends SoftDeletingScope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! SoftDeletesSchemaRepair::hasDeletedAtColumn($model->getTable())) {
            return;
        }

        parent::apply($builder, $model);
    }

    public function extend(Builder $builder): void
    {
        if (! SoftDeletesSchemaRepair::hasDeletedAtColumn($builder->getModel()->getTable())) {
            return;
        }

        parent::extend($builder);
    }
}
