<?php

namespace App\Services\User;

use App\Models\User;
use App\Rules\IranianNationalCode;
use App\Services\Auth\OtpService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserSoftDeletionService
{
    /**
     * @return 'deleted'|'skipped'
     */
    public function delete(User $user): string
    {
        // فقط سفارش‌های فعال (غیرحذف‌شده) مانع soft-delete کاربر می‌شوند؛ سفارش soft-delete از لیست کافی است.
        if ($user->orders()->exists()) {
            return 'skipped';
        }

        $user->delete();

        return 'deleted';
    }

    /**
     * @param  Collection<int, User>|iterable<int, User>  $users
     */
    public function deleteMany(iterable $users): UserSoftDeletionResult
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if ($this->delete($user) === 'skipped') {
                $skipped++;
            } else {
                $deleted++;
            }
        }

        return new UserSoftDeletionResult($deleted, $skipped);
    }

    /**
     * کاربر soft-delete‌شده هنوز phone/email/کدملی را نگه می‌دارد و ایندکس یکتا
     * جلوی ایجاد مجدد را می‌گیرد. قبل از create/edit، رکوردهای حذف‌شدهٔ متعارض را
     * برای همیشه پاک می‌کند تا هویت آزاد شود.
     */
    public function purgeTrashedIdentityConflicts(
        ?string $phone = null,
        ?string $email = null,
        ?string $nationalCode = null,
        ?int $exceptUserId = null,
    ): int {
        $phone = filled($phone) ? app(OtpService::class)->normalizePhone($phone) : null;
        $email = filled($email) ? mb_strtolower(trim($email)) : null;
        $nationalCode = filled($nationalCode) ? IranianNationalCode::normalize($nationalCode) : null;

        if ($phone === null && $email === null && ($nationalCode === null || $nationalCode === '')) {
            return 0;
        }

        $query = User::onlyTrashed()->where(function (Builder $inner) use ($phone, $email, $nationalCode): void {
            if ($phone !== null) {
                $inner->orWhere('phone', $phone);
            }
            if ($email !== null) {
                $inner->orWhere('email', $email);
            }
            if ($nationalCode !== null && $nationalCode !== '') {
                $inner->orWhere('national_code', $nationalCode);
            }
        });

        if ($exceptUserId !== null) {
            $query->whereKeyNot($exceptUserId);
        }

        $purged = 0;

        foreach ($query->get() as $trashed) {
            $trashed->representativeProfile()->delete();
            $trashed->forceDelete();
            $purged++;
        }

        return $purged;
    }
}
