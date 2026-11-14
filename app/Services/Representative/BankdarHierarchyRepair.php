<?php

namespace App\Services\Representative;

use App\Models\User;

/**
 * ترمیم لینک بنکدار → مدیر فروش وقتی نقش‌ها عوض شده‌اند
 * (مثلاً «قدیمی» مدیر فروش شده و بنکدار هنوز created_by_sales_manager_id خالی دارد).
 */
class BankdarHierarchyRepair
{
    /**
     * @return array{linked: int, province_filled: int}
     */
    public function repairMissingSalesManagerLinks(): array
    {
        $linked = 0;
        $provinceFilled = 0;

        $bankdars = User::query()
            ->where('is_bankdar', true)
            ->where(function ($q): void {
                $q->whereNull('created_by_sales_manager_id')
                    ->orWhereNull('bankdar_province_id');
            })
            ->with('representativeProfile')
            ->get();

        foreach ($bankdars as $bankdar) {
            $changed = false;
            $profile = $bankdar->representativeProfile;

            if ($profile && ! $bankdar->created_by_sales_manager_id) {
                $managerId = (int) ($profile->sales_manager_id ?? 0);
                if ($managerId > 0 && $managerId !== (int) $bankdar->id) {
                    $managerIsSalesManager = User::query()
                        ->whereKey($managerId)
                        ->where('is_sales_manager', true)
                        ->exists();

                    if ($managerIsSalesManager) {
                        $bankdar->created_by_sales_manager_id = $managerId;
                        $changed = true;
                        $linked++;
                    }
                }
            }

            if (! $bankdar->bankdar_province_id && $profile?->province_id) {
                $bankdar->bankdar_province_id = (int) $profile->province_id;
                $changed = true;
                $provinceFilled++;
            }

            if ($changed) {
                $bankdar->save();
            }
        }

        return compact('linked', 'province_filled');
    }
}
