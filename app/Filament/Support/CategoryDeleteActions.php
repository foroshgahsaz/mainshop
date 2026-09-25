<?php

namespace App\Filament\Support;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use App\Services\Category\CategoryDeletionResult;
use App\Services\Category\CategoryDeletionService;
use Filament\Actions\DeleteAction as PageDeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use RuntimeException;

class CategoryDeleteActions
{
    public static function tableDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->label('حذف')
            ->iconButton()
            ->requiresConfirmation()
            ->modalDescription('محصولاتی که در سفارش‌ها ثبت شده‌اند به دسته «عمومی» منتقل می‌شوند.')
            ->action(function (Category $record): void {
                static::notify(static::delete($record));
            });
    }

    public static function pageDeleteAction(string $label = 'حذف'): PageDeleteAction
    {
        return PageDeleteAction::make()
            ->label($label)
            ->requiresConfirmation()
            ->modalDescription('محصولاتی که در سفارش‌ها ثبت شده‌اند به دسته «عمومی» منتقل می‌شوند.')
            ->action(function (Category $record, PageDeleteAction $action): void {
                static::notify(static::delete($record));
                $action->redirect(CategoryResource::getUrl('index'));
                throw new Halt;
            });
    }

    protected static function delete(Category $category): CategoryDeletionResult
    {
        try {
            return app(CategoryDeletionService::class)->delete($category);
        } catch (RuntimeException $exception) {
            Notification::make()
                ->title('حذف ممکن نیست')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            throw new Halt;
        }
    }

    protected static function notify(CategoryDeletionResult $result): void
    {
        Notification::make()
            ->title('حذف شد')
            ->body($result->message())
            ->success()
            ->send();
    }
}
