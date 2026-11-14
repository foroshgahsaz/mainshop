<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ورود موقت مدیر سیستم به پنل بنکدار یا نماینده — بدون remember و با بازگشت امن.
 *
 * سشن فقط چند کلید سبک نگه می‌دارد؛ isActive() کوئری DB ندارد.
 */
class AdminImpersonationService
{
    public const SESSION_ACTOR_ID = 'admin_impersonation.actor_id';

    public const SESSION_TARGET_ID = 'admin_impersonation.target_id';

    public const SESSION_ACTOR_NAME = 'admin_impersonation.actor_name';

    public const SESSION_TARGET_NAME = 'admin_impersonation.target_name';

    public const SESSION_TARGET_ROLE = 'admin_impersonation.target_role';

    public const SESSION_RETURN_TAB = 'admin_impersonation.return_tab';

    public function isActive(): bool
    {
        return session()->has(self::SESSION_ACTOR_ID);
    }

    /**
     * دادهٔ بنر بدون کوئری — برای رندر هر درخواست سبک است.
     *
     * @return array{actor_name:string,target_name:string,target_role:string}|null
     */
    public function bannerContext(): ?array
    {
        if (! $this->isActive()) {
            return null;
        }

        return [
            'actor_name' => (string) session(self::SESSION_ACTOR_NAME, 'مدیر سیستم'),
            'target_name' => (string) session(self::SESSION_TARGET_NAME, 'کاربر'),
            'target_role' => (string) session(self::SESSION_TARGET_ROLE, ''),
        ];
    }

    public function canStart(?User $actor, ?User $target): bool
    {
        if (! $actor instanceof User || ! $target instanceof User) {
            return false;
        }

        if (! $actor->isAdmin() || ! $actor->status || $actor->trashed()) {
            return false;
        }

        if ($this->isActive()) {
            return false;
        }

        if ((int) $actor->getKey() === (int) $target->getKey()) {
            return false;
        }

        if (! $target->status || $target->trashed()) {
            return false;
        }

        if ($target->isAdmin() || $target->isSalesManager()) {
            return false;
        }

        $isBankdar = $target->isBankdar();
        $isRep = $target->isRepresentative();

        if ($isBankdar === $isRep) {
            return false;
        }

        return true;
    }

    public function start(User $actor, User $target): string
    {
        abort_unless($this->canStart($actor, $target), 403);

        $role = $target->isBankdar() ? 'bankdar' : 'representative';
        $returnTab = $role === 'bankdar'
            ? User::ADMIN_LIST_SEGMENT_BANKDAR
            : User::ADMIN_LIST_SEGMENT_REPRESENTATIVE;

        session([
            self::SESSION_ACTOR_ID => (int) $actor->getKey(),
            self::SESSION_TARGET_ID => (int) $target->getKey(),
            self::SESSION_ACTOR_NAME => (string) $actor->name,
            self::SESSION_TARGET_NAME => (string) $target->name,
            self::SESSION_TARGET_ROLE => $role,
            self::SESSION_RETURN_TAB => $returnTab,
        ]);

        AdminAccess::flushMemo();
        Auth::login($target, remember: false);
        request()->session()->regenerate();

        Log::info('admin.impersonation.start', [
            'actor_id' => (int) $actor->getKey(),
            'target_id' => (int) $target->getKey(),
            'target_role' => $role,
            'ip' => request()->ip(),
        ]);

        return $role === 'bankdar'
            ? url('/admin')
            : url('/representative');
    }

    public function stop(): string
    {
        abort_unless($this->isActive(), 403);

        $actorId = (int) session(self::SESSION_ACTOR_ID);
        $targetId = (int) session(self::SESSION_TARGET_ID);
        $returnTab = (string) session(self::SESSION_RETURN_TAB, User::ADMIN_LIST_SEGMENT_BANKDAR);

        $actor = User::query()
            ->whereKey($actorId)
            ->where('is_admin', true)
            ->where('status', true)
            ->first();

        $this->forgetSessionKeys();

        if (! $actor instanceof User) {
            AdminAccess::flushMemo();
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            Log::warning('admin.impersonation.stop_actor_missing', [
                'actor_id' => $actorId,
                'target_id' => $targetId,
                'ip' => request()->ip(),
            ]);

            abort(403, 'نشست مدیر سیستم قابل بازیابی نیست.');
        }

        AdminAccess::flushMemo();
        Auth::login($actor, remember: false);
        request()->session()->regenerate();

        Log::info('admin.impersonation.stop', [
            'actor_id' => $actorId,
            'target_id' => $targetId,
            'ip' => request()->ip(),
        ]);

        return url('/admin/users?activeTab='.urlencode($returnTab));
    }

    /** پاک‌سازی کلیدهای impersonation بدون تغییر کاربر فعلی (مثلاً روی Logout). */
    public function forgetSessionKeys(): void
    {
        session()->forget([
            self::SESSION_ACTOR_ID,
            self::SESSION_TARGET_ID,
            self::SESSION_ACTOR_NAME,
            self::SESSION_TARGET_NAME,
            self::SESSION_TARGET_ROLE,
            self::SESSION_RETURN_TAB,
        ]);
    }
}
