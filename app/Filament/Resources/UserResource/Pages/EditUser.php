<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\UserEditTabs;
use App\Models\User;
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
        return [Actions\DeleteAction::make()];
    }

    protected function getFormActions(): array
    {
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
        /** @var User $user */
        $user = $this->getRecord();

        $data = array_merge([
            'name' => $user->name,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'email' => $user->email,
            'status' => $user->status,
            'is_admin' => $user->is_admin,
            'is_author' => $user->is_author,
            'is_representative' => $user->is_representative,
        ], $data);

        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }

        return CreateUser::applyAccessFieldsFromForm($this->form, $data);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $data['user_kind'] = CreateUser::resolveUserKindFromFlags($data);

        return $data;
    }

    public function mount(int | string $record): void
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
