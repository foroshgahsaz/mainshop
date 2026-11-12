<?php

namespace App\Services\Representative;

use App\Models\Province;
use App\Models\RepresentativeProfile;
use App\Models\SalesRegion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesRegionService
{
    /** @var array<int, list<int>> */
    private array $provinceIdsMemo = [];

    /** استان‌های قابل تخصیص به بنکدار (همه استان‌های دارای منطقه). */
    /** @return list<int> */
    public function provinceIdsAvailableForBankdar(): array
    {
        return Province::query()
            ->whereNotNull('sales_region_id')
            ->orderBy('position')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array<int, string> */
    public function provinceOptionsAvailableForBankdar(): array
    {
        $ids = $this->provinceIdsAvailableForBankdar();

        if ($ids === []) {
            return [];
        }

        return Province::query()->whereIn('id', $ids)->orderBy('position')->pluck('name', 'id')->all();
    }

    /**
     * استان‌های تحت مدیریت: بنکدار = استان خودش؛ مدیر فروش = استان بنکدارهایش یا همه مناطق.
     *
     * @return list<int>
     */
    public function provinceIdsForManager(?int $managerId): array
    {
        if (! $managerId) {
            return [];
        }

        if (isset($this->provinceIdsMemo[$managerId])) {
            return $this->provinceIdsMemo[$managerId];
        }

        $user = User::query()->find($managerId);

        if (! $user) {
            return $this->provinceIdsMemo[$managerId] = [];
        }

        if ($user->isBankdar()) {
            $id = (int) ($user->bankdar_province_id ?? 0);

            return $this->provinceIdsMemo[$managerId] = $id > 0 ? [$id] : [];
        }

        if ($user->isSalesManager()) {
            $fromBankdars = User::query()
                ->where('is_bankdar', true)
                ->where('created_by_sales_manager_id', $managerId)
                ->whereNotNull('bankdar_province_id')
                ->pluck('bankdar_province_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($fromBankdars !== []) {
                return $this->provinceIdsMemo[$managerId] = $fromBankdars;
            }

            // مدیر فروش بدون بنکدار هنوز می‌تواند از استان‌های مناطق انتخاب کند
            return $this->provinceIdsMemo[$managerId] = $this->provinceIdsAvailableForBankdar();
        }

        // سازگاری با داده قدیمی: sales_regions.sales_manager_id
        return $this->provinceIdsMemo[$managerId] = Province::query()
            ->whereHas('salesRegion', fn (Builder $q) => $q->where('sales_manager_id', $managerId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /** @return array<int, string> */
    public function provinceOptionsForManager(?int $managerId): array
    {
        $ids = $this->provinceIdsForManager($managerId);

        if ($ids === []) {
            return [];
        }

        return Province::query()->whereIn('id', $ids)->orderBy('position')->pluck('name', 'id')->all();
    }

    /** گزینه‌های بنکدار برای انتساب نماینده (ادمین). */
    /** @return array<int, string> */
    public function managerOptions(): array
    {
        return User::query()
            ->where('is_bankdar', true)
            ->where('status', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function assertProvinceBelongsToManager(?int $managerId, ?int $provinceId): void
    {
        if (! $managerId) {
            throw ValidationException::withMessages(['sales_manager_id' => 'بنکدار نماینده الزامی است.']);
        }

        if (! $provinceId || ! in_array($provinceId, $this->provinceIdsForManager($managerId), true)) {
            throw ValidationException::withMessages(['province_id' => 'استان فعالیت باید استان همین بنکدار باشد.']);
        }
    }

    public function assertBankdarProvinceAvailable(?int $provinceId): void
    {
        if (! $provinceId || ! in_array($provinceId, $this->provinceIdsAvailableForBankdar(), true)) {
            throw ValidationException::withMessages([
                'bankdar_province_id' => 'استان بنکدار باید یکی از استان‌های تعریف‌شده در مناطق فروش باشد.',
            ]);
        }
    }

    /**
     * نمایندگان قابل‌مشاهده برای بنکدار / مدیر فروش.
     */
    public function scopeRepresentativesManagedBy(Builder $users, int $actorId): Builder
    {
        $actor = User::query()->find($actorId);

        if (! $actor) {
            return $users->whereRaw('1 = 0');
        }

        if ($actor->isBankdar()) {
            return $users
                ->where('is_representative', true)
                ->whereHas('representativeProfile', fn (Builder $q) => $q->where('sales_manager_id', $actorId));
        }

        if ($actor->isSalesManager()) {
            $bankdarIds = User::query()
                ->where('is_bankdar', true)
                ->where('created_by_sales_manager_id', $actorId)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($bankdarIds === []) {
                return $users->whereRaw('1 = 0');
            }

            return $users
                ->where('is_representative', true)
                ->whereHas('representativeProfile', fn (Builder $q) => $q->whereIn('sales_manager_id', $bankdarIds));
        }

        $provinceIds = $this->provinceIdsForManager($actorId);

        return $users
            ->where('is_representative', true)
            ->whereHas('representativeProfile', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('sales_manager_id', $actorId)
                ->when($provinceIds !== [], fn (Builder $q) => $q->orWhereIn('province_id', $provinceIds))));
    }

    public function scopeBankdarsManagedBy(Builder $users, int $salesManagerId): Builder
    {
        return $users
            ->where('is_bankdar', true)
            ->where('created_by_sales_manager_id', $salesManagerId);
    }

    public function managesRepresentative(User $actor, User $representative): bool
    {
        if (! $representative->isRepresentative()) {
            return false;
        }

        $profile = $representative->representativeProfile;

        if (! $profile) {
            return false;
        }

        if ($actor->isBankdar()) {
            return (int) $profile->sales_manager_id === (int) $actor->id;
        }

        if ($actor->isSalesManager()) {
            $bankdarId = (int) $profile->sales_manager_id;

            return $bankdarId > 0 && User::query()
                ->whereKey($bankdarId)
                ->where('is_bankdar', true)
                ->where('created_by_sales_manager_id', $actor->id)
                ->exists();
        }

        return (int) $profile->sales_manager_id === (int) $actor->id
            || in_array((int) $profile->province_id, $this->provinceIdsForManager((int) $actor->id), true);
    }

    public function managesBankdar(User $salesManager, User $bankdar): bool
    {
        if (! $salesManager->isSalesManager() || ! $bankdar->isBankdar()) {
            return false;
        }

        return (int) $bankdar->created_by_sales_manager_id === (int) $salesManager->id;
    }

    public function managesUser(User $actor, User $user): bool
    {
        if ($user->isBankdar() && $actor->isSalesManager()) {
            return $this->managesBankdar($actor, $user);
        }

        if ($user->isRepresentative()) {
            return $this->managesRepresentative($actor, $user);
        }

        if (! $user->created_by_representative_id) {
            return false;
        }

        return $this->scopeRepresentativesManagedBy(User::query(), (int) $actor->id)
            ->whereKey($user->created_by_representative_id)
            ->exists();
    }

    public function scopeCustomersOfManagedRepresentatives(Builder $users, int $actorId): Builder
    {
        return $users->whereIn(
            'created_by_representative_id',
            $this->scopeRepresentativesManagedBy(User::query(), $actorId)->select('id')
        );
    }

    /** نمایندگانی که استان فعالیتشان در این منطقه است. */
    public function representativesForRegion(SalesRegion $region): Builder
    {
        $provinceIds = $region->relationLoaded('provinces')
            ? $region->provinces->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $region->provinces()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($provinceIds === []) {
            return User::query()->whereRaw('1 = 0');
        }

        return User::query()
            ->where('is_representative', true)
            ->with(['representativeProfile.province:id,name'])
            ->whereHas('representativeProfile', fn (Builder $q) => $q->whereIn('province_id', $provinceIds));
    }

    public function bankdarsForProvince(int $provinceId): Builder
    {
        return User::query()
            ->where('is_bankdar', true)
            ->where('bankdar_province_id', $provinceId)
            ->orderBy('name');
    }

    /** @return list<int> */
    public function managedRepresentativeIds(int $actorId): array
    {
        return $this->scopeRepresentativesManagedBy(User::query(), $actorId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function scopeOrdersManagedBy(Builder $orders, int $actorId): Builder
    {
        $repIds = $this->managedRepresentativeIds($actorId);

        if ($repIds === []) {
            return $orders->whereRaw('1 = 0');
        }

        return $orders->whereIn('representative_id', $repIds);
    }

    /**
     * @param  list<int|string>  $provinceIds
     */
    public function save(SalesRegion $region, array $attributes, array $provinceIds): SalesRegion
    {
        $provinceIds = array_values(array_unique(array_map('intval', $provinceIds)));

        unset($attributes['sales_manager_id']);

        if ($provinceIds === []) {
            throw ValidationException::withMessages(['province_ids' => 'حداقل یک استان انتخاب کنید.']);
        }

        $taken = Province::query()
            ->whereIn('id', $provinceIds)
            ->whereNotNull('sales_region_id')
            ->when($region->exists, fn (Builder $q) => $q->where('sales_region_id', '!=', $region->id))
            ->pluck('name');

        if ($taken->isNotEmpty()) {
            throw ValidationException::withMessages([
                'province_ids' => 'این استان‌ها در منطقه دیگری هستند: '.$taken->implode('، '),
            ]);
        }

        return DB::transaction(function () use ($region, $attributes, $provinceIds) {
            $region->fill($attributes)->save();

            Province::query()
                ->where('sales_region_id', $region->id)
                ->whereNotIn('id', $provinceIds)
                ->update(['sales_region_id' => null]);

            Province::query()->whereIn('id', $provinceIds)->update(['sales_region_id' => $region->id]);

            $this->provinceIdsMemo = [];

            return $region->fresh(['provinces', 'salesManager']);
        });
    }
}
