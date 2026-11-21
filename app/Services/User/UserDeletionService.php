<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserDeletionService
{
    public function __construct(
        protected UserForceDeletionService $forceDeletion,
    ) {}

    /**
     * @return 'deleted'|'skipped'
     */
    public function delete(User $user, bool $allowOrdersPurge = false): string
    {
        if (auth()->id() !== null && (int) auth()->id() === (int) $user->getKey()) {
            throw new RuntimeException('نمی‌توانید حساب ورود فعلی خود را حذف کنید.');
        }

        if ($user->orders()->exists()) {
            if ($allowOrdersPurge) {
                $this->forceDeletion->forceDelete($user);

                return 'deleted';
            }

            return 'skipped';
        }

        DB::transaction(function () use ($user): void {
            $user->refresh();

            $user->payments()->delete();
            $user->cartItems()->delete();
            $user->addresses()->delete();
            $user->couponUsages()->delete();
            $user->wishlists()->delete();
            $user->reviews()->delete();
            $user->productQuestions()->delete();
            $user->representativeProfile?->delete();

            $user->delete();
        });

        return 'deleted';
    }

    /**
     * @param  Collection<int, User>|iterable<int, User>  $users
     */
    public function deleteMany(iterable $users, bool $allowOrdersPurge = false): UserDeletionResult
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if ($this->delete($user, $allowOrdersPurge) === 'skipped') {
                $skipped++;
            } else {
                $deleted++;
            }
        }

        return new UserDeletionResult($deleted, $skipped);
    }
}