<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\User;
use App\Services\Order\BankdarOrderService;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'ایجاد سفارش';

    public static function canAccess(array $parameters = []): bool
    {
        return AdminAccess::canCreateOrdersInAdmin();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('سفارش پیش‌نویس')
                ->description('سفارش به‌صورت پیش‌نویس زیر نماینده انتخاب‌شده ساخته می‌شود. سپس از صفحه جزئیات قابل ویرایش است.')
                ->schema([
                    Forms\Components\Select::make('representative_id')
                        ->label('نماینده')
                        ->options(fn (): array => static::representativeOptions())
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('user_id', null)),
                    Forms\Components\Select::make('user_id')
                        ->label('مشتری')
                        ->options(fn (Forms\Get $get): array => static::customerOptions((int) ($get('representative_id') ?? 0)))
                        ->required()
                        ->searchable()
                        ->helperText('فقط مشتریان همان نماینده. مشتری باید حداقل یک آدرس داشته باشد.'),
                ])
                ->columns(2),
        ]);
    }

    /** @return array<int, string> */
    protected static function representativeOptions(): array
    {
        if (AdminAccess::isBankdarOnly()) {
            return app(SalesRegionService::class)
                ->scopeRepresentativesManagedBy(User::query(), (int) auth()->id())
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();
        }

        return User::query()
            ->where('is_representative', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    protected static function customerOptions(int $representativeId): array
    {
        if ($representativeId <= 0) {
            return [];
        }

        return User::query()
            ->where('created_by_representative_id', $representativeId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->mapWithKeys(fn (User $u) => [
                $u->id => trim($u->name.($u->phone ? ' — '.$u->phone : '')),
            ])
            ->all();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();
        if (! $actor instanceof User) {
            throw ValidationException::withMessages(['user_id' => 'ورود لازم است.']);
        }

        if (AdminAccess::isBankdarOnly($actor)) {
            return app(BankdarOrderService::class)->createDraft(
                $actor,
                (int) ($data['representative_id'] ?? 0),
                (int) ($data['user_id'] ?? 0),
            );
        }

        $representative = User::query()->findOrFail((int) ($data['representative_id'] ?? 0));
        $customer = User::query()->findOrFail((int) ($data['user_id'] ?? 0));

        return app(\App\Services\Representative\RepresentativeDraftOrderService::class)
            ->createDraft($representative, $customer);
    }

    protected function getRedirectUrl(): string
    {
        return OrderResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('سفارش پیش‌نویس ایجاد شد')
            ->body('از صفحه جزئیات می‌توانید اقلام و وضعیت را ویرایش کنید.');
    }
}
