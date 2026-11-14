<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\UserEditTabs;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\AdminAccess;
use Filament\Actions;
use Illuminate\Contracts\Support\Htmlable;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public string $userEditTab = UserEditTabs::TAB_PROFILE;

    public function getActiveUserEditTab(): string
    {
        return $this->userEditTab;
    }

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
        if (AdminAccess::isLimitedStaffOnly()
            && auth()->id() !== $this->getRecord()->getKey()
            && ! UserResource::canEdit($this->getRecord())) {
            return [];
        }

        if (UserEditTabs::isReadOnly($this->userEditTab)) {
            return [];
        }

        return parent::getFormActions();
    }

    protected function getRedirectUrl(): string
    {
        $url = static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);

        return $url.'?tab='.urlencode($this->userEditTab);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (AdminAccess::isLimitedStaffOnly()
            && auth()->id() !== $this->getRecord()->getKey()
            && ! UserResource::canEdit($this->getRecord())) {
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

        if (AdminAccess::isLimitedStaffOnly()) {
            foreach (['is_admin', 'is_author', 'is_representative', 'is_sales_manager', 'is_bankdar'] as $flag) {
                $data[$flag] = (bool) $user->{$flag};
            }

            // مدیر فروش نمی‌تواند انتساب بنکدار به مدیر دیگر را عوض کند.
            if (AdminAccess::isSalesManagerOnly()) {
                $data['created_by_sales_manager_id'] = $user->created_by_sales_manager_id;
            }

            return CreateUser::stripVirtualAccessFormKeys($data);
        }

        if ($this->userEditTab === UserEditTabs::TAB_ACCESS) {
            return CreateUser::applyAccessFieldsFromForm($this->form, $data);
        }

        return CreateUser::stripVirtualAccessFormKeys($data);
    }

    /** @return array<string, mixed> */
    protected function preservedAttributesFromUser(User $user): array
    {
        return [
            'name' => $user->name,
            'slug' => $user->slug,
            'bio' => $user->bio,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'national_code' => $user->national_code,
            'email' => $user->email,
            'status' => $user->status,
            'phone_verified_at' => $user->phone_verified_at,
            'email_verified_at' => $user->email_verified_at,
            'created_by_representative_id' => $user->created_by_representative_id,
            'is_admin' => $user->is_admin,
            'is_author' => $user->is_author,
            'is_representative' => $user->is_representative,
            'is_sales_manager' => $user->is_sales_manager,
            'is_bankdar' => $user->is_bankdar,
            'bankdar_province_id' => $user->bankdar_province_id,
            'created_by_sales_manager_id' => $user->created_by_sales_manager_id,
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $data['user_kind'] = CreateUser::resolveUserKindFromFlags($data);
        $data['staff_role'] = CreateUser::resolveStaffRoleFromFlags($data);

        $user = $this->getRecord();
        if ($user instanceof User) {
            $user->loadMissing([
                'representativeProfile.salesManager',
                'representativeProfile.province.salesRegion',
                'representativeProfile.province',
                'representativeProfile.city',
            ]);

            $profile = $user->representativeProfile;
            if ($profile) {
                $data['representativeProfile'] = [
                    'sales_manager_id' => $profile->sales_manager_id,
                    'province_id' => $profile->province_id,
                    'city_id' => $profile->city_id,
                    'max_active_reservations' => $profile->max_active_reservations,
                ];
            }
        }

        return $data;
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        if (AdminAccess::isLimitedStaffOnly()
            && auth()->id() !== $this->getRecord()->getKey()
            && ! UserResource::canEdit($this->getRecord())) {
            return;
        }

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }

    public function mount(int|string $record): void
    {
        $this->userEditTab = UserEditTabs::resolveActive('edit');

        parent::mount($record);
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
