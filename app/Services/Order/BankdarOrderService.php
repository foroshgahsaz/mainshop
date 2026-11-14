<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\User;
use App\Services\Representative\RepresentativeDraftOrderService;
use App\Services\Representative\SalesRegionService;
use Illuminate\Validation\ValidationException;

class BankdarOrderService
{
    public function __construct(
        protected SalesRegionService $regions,
        protected RepresentativeDraftOrderService $drafts,
    ) {}

    public function createDraft(User $bankdar, int $representativeId, int $customerId): Order
    {
        if (! $bankdar->isBankdar()) {
            throw ValidationException::withMessages([
                'representative_id' => 'فقط بنکدار می‌تواند از این مسیر سفارش بسازد.',
            ]);
        }

        $representative = User::query()->find($representativeId);
        if (! $representative || ! $this->regions->managesRepresentative($bankdar, $representative)) {
            throw ValidationException::withMessages([
                'representative_id' => 'نماینده انتخاب‌شده زیرمجموعه شما نیست.',
            ]);
        }

        $customer = User::query()->find($customerId);
        if (! $customer) {
            throw ValidationException::withMessages([
                'user_id' => 'مشتری یافت نشد.',
            ]);
        }

        $ownsCustomer = (int) $customer->created_by_representative_id === (int) $representative->id
            || $this->regions->managesUser($bankdar, $customer);

        if (! $ownsCustomer) {
            throw ValidationException::withMessages([
                'user_id' => 'مشتری باید زیرمجموعه نمایندگان شما باشد.',
            ]);
        }

        return $this->drafts->createDraft($representative, $customer);
    }
}
