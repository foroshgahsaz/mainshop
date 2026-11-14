<?php

namespace App\Services\Representative;

use App\Models\User;
use Illuminate\Support\Collection;

class BankdarSubtreeService
{
    /**
     * درخت: مدیر فروش → بنکدار → نمایندگان.
     *
     * @return array{
     *   bankdar: array{id:int,name:string,phone:?string,province:?string,url:?string},
     *   sales_manager: array{id:int,name:string,phone:?string,url:?string}|null,
     *   representatives: list<array{id:int,name:string,phone:?string,province:?string,city:?string,url:?string,customers_count:int}>
     * }
     */
    public function treeForBankdar(User $bankdar): array
    {
        $bankdar->loadMissing([
            'bankdarProvince:id,name',
            'createdBySalesManager:id,name,phone',
        ]);

        if (! $bankdar->isBankdar()) {
            abort(404);
        }

        $salesManager = $bankdar->createdBySalesManager;

        /** @var Collection<int, User> $reps */
        $reps = User::query()
            ->where('is_representative', true)
            ->whereHas('representativeProfile', fn ($q) => $q->where('sales_manager_id', $bankdar->id))
            ->with(['representativeProfile.province:id,name', 'representativeProfile.city:id,name'])
            ->withCount('customersCreated')
            ->orderBy('name')
            ->get();

        return [
            'sales_manager' => $salesManager
                ? $this->personNode($salesManager, role: 'sales_manager')
                : null,
            'bankdar' => $this->personNode(
                $bankdar,
                role: 'bankdar',
                province: $bankdar->bankdarProvince?->name,
            ),
            'representatives' => $reps->map(function (User $rep): array {
                $profile = $rep->representativeProfile;

                return $this->personNode(
                    $rep,
                    role: 'representative',
                    province: $profile?->province?->name,
                    city: $profile?->city?->name,
                    customersCount: (int) ($rep->customers_created_count ?? 0),
                );
            })->values()->all(),
        ];
    }

    /**
     * @return array{id:int,name:string,phone:?string,role:string,province:?string,city:?string,url:?string,customers_count:int}
     */
    protected function personNode(
        User $user,
        string $role,
        ?string $province = null,
        ?string $city = null,
        int $customersCount = 0,
    ): array {
        $url = null;
        try {
            $url = \App\Filament\Resources\UserResource::getUrl('edit', ['record' => $user]);
        } catch (\Throwable) {
            $url = null;
        }

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'phone' => $user->phone ? (string) $user->phone : null,
            'role' => $role,
            'province' => $province,
            'city' => $city,
            'url' => $url,
            'customers_count' => $customersCount,
        ];
    }
}
