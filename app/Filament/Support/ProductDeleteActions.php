<?php

namespace App\Filament\Support;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Services\Product\ProductDeletionResult;
use App\Services\Product\ProductDeletionService;
use Filament\Actions\DeleteAction as PageDeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

class ProductDeleteActions
{
    public static function tableDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->label('حذف')
            ->iconButton()
            ->action(function (Product $record): void {
                static::notifySingle(app(ProductDeletionService::class)->delete($record));
            });
    }

    public static function tableBulkDeleteAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('delete')
            ->label('حذف')
            ->requiresConfirmation()
            ->modalHeading('حذف محصولات انتخاب‌شده')
            ->modalDescription('محصولاتی که در سفارش‌ها ثبت شده‌اند حذف کامل نمی‌شوند و به‌صورت آرشیو غیرفعال می‌شوند.')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->action(function (Collection $records): void {
                static::notifyBulk(app(ProductDeletionService::class)->deleteMany($records));
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function pageDeleteAction(string $label = 'حذف'): PageDeleteAction
    {
        return PageDeleteAction::make()
            ->label($label)
            ->action(function (Product $record, PageDeleteAction $action): void {
                static::notifySingle(app(ProductDeletionService::class)->delete($record));
                static::redirectAfterPageDelete($action, ProductResource::getUrl('index'));
            });
    }

    protected static function redirectAfterPageDelete(PageDeleteAction $action, string $url): void
    {
        $action->redirect($url);

        throw new Halt;
    }

    protected static function notifySingle(string $outcome): void
    {
        Notification::make()
            ->title($outcome === 'archived'
                ? 'محصول در سفارش‌ها استفاده شده؛ غیرفعال و آرشیو شد'
                : 'حذف شد')
            ->success()
            ->send();
    }

    protected static function notifyBulk(ProductDeletionResult $result): void
    {
        Notification::make()
            ->title('عملیات حذف انجام شد')
            ->body($result->message())
            ->success()
            ->send();
    }
}
