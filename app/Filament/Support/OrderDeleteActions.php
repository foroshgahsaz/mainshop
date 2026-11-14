<?php

namespace App\Filament\Support;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderDeletionResult;
use App\Services\Order\OrderDeletionService;
use App\Support\AdminAccess;
use Filament\Actions\DeleteAction as PageDeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

class OrderDeleteActions
{
    public static function tableDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->label('حذف')
            ->iconButton()
            ->visible(fn (): bool => AdminAccess::canSoftDeleteShopRecordsInAdmin())
            ->action(function (Order $record): void {
                static::notifySingle(app(OrderDeletionService::class)->delete($record));
            });
    }

    public static function tableBulkDeleteAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('delete')
            ->label('حذف انتخاب‌شده‌ها')
            ->requiresConfirmation()
            ->modalHeading('حذف سفارش‌های انتخاب‌شده')
            ->modalDescription('سفارش‌هایی که پرداخت موفق دارند حذف نمی‌شوند. حذف یعنی از فروشگاه و داشبورد بنکدار پنهان می‌شود؛ مدیر سیستم می‌تواند برگرداند.')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->visible(fn (): bool => AdminAccess::canSoftDeleteShopRecordsInAdmin())
            ->action(function (Collection $records): void {
                static::notifyBulk(app(OrderDeletionService::class)->deleteMany($records));
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function pageDeleteAction(string $label = 'حذف سفارش'): PageDeleteAction
    {
        return PageDeleteAction::make()
            ->label($label)
            ->color('danger')
            ->visible(fn (): bool => AdminAccess::canSoftDeleteShopRecordsInAdmin())
            ->requiresConfirmation()
            ->modalHeading('حذف سفارش')
            ->modalDescription('سفارش‌های با پرداخت موفق قابل حذف نیستند. حذف یعنی از فروشگاه و داشبورد بنکدار پنهان می‌شود؛ مدیر سیستم می‌تواند برگرداند.')
            ->action(function (Order $record, PageDeleteAction $action): void {
                $outcome = app(OrderDeletionService::class)->delete($record);
                static::notifySingle($outcome);

                if ($outcome === 'deleted') {
                    $action->redirect(OrderResource::getUrl('index'));
                    throw new Halt;
                }
            });
    }

    protected static function notifySingle(string $outcome): void
    {
        if ($outcome === 'skipped') {
            Notification::make()
                ->title('سفارش قابل حذف نیست')
                ->body('این سفارش پرداخت موفق دارد. ابتدا مرجوعی انجام دهید یا فقط سفارش‌های بدون تسویه را حذف کنید.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('سفارش از لیست پنهان شد')
            ->body('تا بازیابی توسط مدیر سیستم در فروشگاه و داشبورد بنکدار دیده نمی‌شود.')
            ->success()
            ->send();
    }

    protected static function notifyBulk(OrderDeletionResult $result): void
    {
        Notification::make()
            ->title('عملیات حذف انجام شد')
            ->body($result->message())
            ->success()
            ->send();
    }
}
