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

    /** @return list<int> */
    public function provinceIdsForManager(?int $managerId): array
    {
        if (! $managerId) {
            return [];
        }

        return $this->provinceIdsMemo[$managerId] ??= Province::query()
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

    /** @return array<int, string> */
    public function managerOptions(): array
    {
        return User::query()
            ->where('is_sales_manager', true)
            ->whereHas('salesRegions')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function assertProvinceBelongsToManager(?int $managerId, ?int $provinceId): void
    {
        if (! $managerId) {
            throw ValidationException::withMessages(['sales_manager_id' => 'مدیر منطقه نماینده الزامی است.']);
        }

        if (! $provinceId || ! in_array($provinceId, $this->provinceIdsForManager($managerId), true)) {
            throw ValidationException::withMessages(['province_id' => 'استان فعالیت باید در یکی از مناطق همین مدیر منطقه باشد.']);
        }
    }

    /**
     * Representatives a sales manager may see/edit: assigned to them, or active in one of their provinces.
     */
    public function scopeRepresentativesManagedBy(Builder $users, int $managerId): Builder
    {
        $provinceIds = $this->provinceIdsForManager($managerId);

        return $users
            ->where('is_representative', true)
            ->whereHas('representativeProfile', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('sales_manager_id', $managerId)
                ->when($provinceIds !== [], fn (Builder $q) => $q->orWhereIn('province_id', $provinceIds))));
    }

    public function managesRepresentative(User $manager, User $representative): bool
    {
        if (! $representative->isRepresentative()) {
            return false;
        }

        $profile = $representative->representativeProfile;

        if (! $profile) {
            return false;
        }

        return (int) $profile->sales_manager_id === (int) $manager->id
            || in_array((int) $profile->province_id, $this->provinceIdsForManager((int) $manager->id), true);
    }

    public function managesUser(User $manager, User $user): bool
    {
        if ($user->isRepresentative()) {
            return $this->managesRepresentative($manager, $user);
        }

        if (! $user->created_by_representative_id) {
            return false;
        }

        return $this->scopeRepresentativesManagedBy(User::query(), (int) $manager->id)
            ->whereKey($user->created_by_representative_id)
            ->exists();
    }

    public function scopeCustomersOfManagedRepresentatives(Builder $users, int $managerId): Builder
    {
        return $users->whereIn(
            'created_by_representative_id',
            $this->scopeRepresentativesManagedBy(User::query(), $managerId)->select('id')
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

    /**
     * @param  list<int|string>  $provinceIds
     */
    public function save(SalesRegion $region, array $attributes, array $provinceIds): SalesRegion
    {
        $provinceIds = array_values(array_unique(array_map('intval', $provinceIds)));

        if (empty($attributes['sales_manager_id'])) {
            throw ValidationException::withMessages(['sales_manager_id' => 'مدیر منطقه الزامی است.']);
        }

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

            RepresentativeProfile::query()
                ->whereIn('province_id', $provinceIds)
                ->update(['sales_manager_id' => $region->sales_manager_id]);

            $this->provinceIdsMemo = [];

            return $region->fresh(['provinces', 'salesManager']);
        });
    }
}
