<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\UserEditTabs;
use App\Models\User;
use App\Support\AdminAccess;
use App\Services\Auth\OtpService;
use Filament\Actions;
use Illuminate\Contracts\Support\Htmlable;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string|Htmlable
    {
        $record = $this->getRecord();

        if ($record instanceof User && auth()->id() === $record->getKey()) {
            return 'مدیریت پروفایل';
        }

        return parent::getHeading();
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        if ($record instanceof User && auth()->id() === $record->getKey()) {
            return 'اطلاعات حساب، تصویر پروفایل و دسترسی‌های شما';
        }

        return parent::getSubheading();
    }

    protected function getHeaderActions(): array
    {
        if (! AdminAccess::canManageShopInAdmin()) {
            return [];
        }

        return [Actions\DeleteAction::make()];
    }

    protected function getFormActions(): array
    {
        if (AdminAccess::isSalesManagerOnly() && auth()->id() !== $this->getRecord()->getKey()) {
            return [];
        }

        if (UserEditTabs::isReadOnly(UserEditTabs::resolveActive('edit'))) {
            return [];
        }

        return parent::getFormActions();
    }

    protected function getRedirectUrl(): string
    {
        $url = static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
        $tab = request()->query('tab');

        if (is_string($tab) && $tab !== '') {
            return $url.'?tab='.urlencode($tab);
        }

        return $url;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (AdminAccess::isSalesManagerOnly() && auth()->id() !== $this->getRecord()->getKey()) {
            return $data;
        }

        /** @var User $user */
        $user = $this->getRecord();

        $data = array_merge($this->preservedAttributesFromUser($user), $data);

        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }

        if (filled($data['phone'] ?? null)) {
            $data['phone'] = app(OtpService::class)->normalizePhone((string) $data['phone']);
        }

        $tab = UserEditTabs::resolveActive('edit');

        if ($tab === UserEditTabs::TAB_ACCESS) {
            return CreateUser::applyAccessFieldsFromForm($this->form, $data);
        }

        return CreateUser::stripVirtualAccessFormKeys($data);
    }

    /** @return array<string, mixed> */
    protected function preservedAttributesFromUser(User $user): array
    {
        return [
            'name' => $user->name,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'email' => $user->email,
            'status' => $user->status,
            'is_admin' => $user->is_admin,
            'is_author' => $user->is_author,
            'is_representative' => $user->is_representative,
            'is_sales_manager' => $user->is_sales_manager,
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $data['user_kind'] = CreateUser::resolveUserKindFromFlags($data);

        return $data;
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        if (AdminAccess::isSalesManagerOnly() && auth()->id() !== $this->getRecord()->getKey()) {
            return;
        }

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->loadMissing([
            'representativeProfile.province',
            'representativeProfile.city',
        ]);
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->getRecord()->refresh();

        if (! $user->is_representative) {
            return;
        }

        if ($user->representativeProfile) {
            return;
        }

        $user->representativeProfile()->create([
            'max_active_reservations' => 3,
        ]);
    }
}
