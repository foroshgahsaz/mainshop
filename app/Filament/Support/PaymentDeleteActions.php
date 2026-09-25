<?php

namespace App\Filament\Support;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\Payment\PaymentDeletionResult;
use App\Services\Payment\PaymentDeletionService;
use Filament\Actions\DeleteAction as PageDeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

class PaymentDeleteActions
{
    public static function tableDeleteAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->label('حذف')
            ->iconButton()
            ->action(function (Payment $record): void {
                static::notifySingle(app(PaymentDeletionService::class)->delete($record));
            });
    }

    public static function tableBulkDeleteAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('delete')
            ->label('حذف')
            ->requiresConfirmation()
            ->modalHeading('حذف پرداخت‌های انتخاب‌شده')
            ->modalDescription('پرداخت‌های موفق حذف نمی‌شوند. برای آن‌ها ابتدا مرجوعی انجام دهید.')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->action(function (Collection $records): void {
                static::notifyBulk(app(PaymentDeletionService::class)->deleteMany($records));
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function pageDeleteAction(string $label = 'حذف'): PageDeleteAction
    {
        return PageDeleteAction::make()
            ->label($label)
            ->requiresConfirmation()
            ->modalDescription('پرداخت‌های موفق قابل حذف نیستند. برای پرداخت تارا ابتدا مرجوعی انجام دهید.')
            ->action(function (Payment $record, PageDeleteAction $action): void {
                $outcome = app(PaymentDeletionService::class)->delete($record);
                static::notifySingle($outcome);

                if ($outcome === 'deleted') {
                    $action->redirect(PaymentResource::getUrl('index'));
                    throw new Halt;
                }
            });
    }

    protected static function notifySingle(string $outcome): void
    {
        if ($outcome === 'skipped') {
            Notification::make()
                ->title('پرداخت موفق قابل حذف نیست')
                ->body('ابتدا مرجوعی انجام دهید یا فقط پرداخت‌های ناموفق را حذف کنید.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('حذف شد')
            ->success()
            ->send();
    }

    protected static function notifyBulk(PaymentDeletionResult $result): void
    {
        Notification::make()
            ->title('عملیات حذف انجام شد')
            ->body($result->message())
            ->success()
            ->send();
    }
}
