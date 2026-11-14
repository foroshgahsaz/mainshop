<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Shop\SoftDeletesSchemaRepair;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ورود موقت مدیر سیستم به پنل مدیر فروش / بنکدار / نماینده — بدون remember و با بازگشت امن.
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

        if (! $actor->isAdmin() || ! $actor->status || $this->isTrashedSafe($actor)) {
            return false;
        }

        if ($this->isActive()) {
            return false;
        }

        if ((int) $actor->getKey() === (int) $target->getKey()) {
            return false;
        }

        if (! $target->status || $this->isTrashedSafe($target)) {
            return false;
        }

        if ($target->isAdmin()) {
            return false;
        }

        return $this->resolveTargetRole($target) !== null;
    }

    public function start(User $actor, User $target): string
    {
        abort_unless($this->canStart($actor, $target), 403);

        // best-effort — خرابی repair نباید خودِ ورود موقت را ۵۰۰ کند
        $this->ensureSoftDeletesSchemaQuietly();

        $role = $this->resolveTargetRole($target);
        abort_unless($role !== null, 403);

        $returnTab = match ($role) {
            'sales_manager' => User::ADMIN_LIST_SEGMENT_SALES_MANAGER,
            'bankdar' => User::ADMIN_LIST_SEGMENT_BANKDAR,
            default => User::ADMIN_LIST_SEGMENT_REPRESENTATIVE,
        };

        // اول login (خودش session را migrate می‌کند)، بعد کلیدهای impersonation
        // تا regenerate دوم کلیدها را از دست ندهد / قفل سشن نسازد.
        AdminAccess::flushMemo();
        Auth::login($target, remember: false);
        $this->syncPasswordHashForAuthenticateSession($target);

        session([
            self::SESSION_ACTOR_ID => (int) $actor->getKey(),
            self::SESSION_TARGET_ID => (int) $target->getKey(),
            self::SESSION_ACTOR_NAME => (string) $actor->name,
            self::SESSION_TARGET_NAME => (string) $target->name,
            self::SESSION_TARGET_ROLE => $role,
            self::SESSION_RETURN_TAB => $returnTab,
        ]);

        try {
            Log::info('admin.impersonation.start', [
                'actor_id' => (int) $actor->getKey(),
                'target_id' => (int) $target->getKey(),
                'target_role' => $role,
                'ip' => request()->ip(),
            ]);
        } catch (\Throwable) {
            // لاگ خراب = ورود موقت نباید بترکد
        }

        return $role === 'representative'
            ? url('/representative')
            : url('/admin');
    }

    public function stop(): string
    {
        abort_unless($this->isActive(), 403);

        $this->ensureSoftDeletesSchemaQuietly();

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

            try {
                Log::warning('admin.impersonation.stop_actor_missing', [
                    'actor_id' => $actorId,
                    'target_id' => $targetId,
                    'ip' => request()->ip(),
                ]);
            } catch (\Throwable) {
            }

            abort(403, 'نشست مدیر سیستم قابل بازیابی نیست.');
        }

        AdminAccess::flushMemo();
        Auth::login($actor, remember: false);
        $this->syncPasswordHashForAuthenticateSession($actor);

        try {
            Log::info('admin.impersonation.stop', [
                'actor_id' => $actorId,
                'target_id' => $targetId,
                'ip' => request()->ip(),
            ]);
        } catch (\Throwable) {
        }

        return url('/admin/users?activeTab='.urlencode($returnTab));
    }

    /**
     * Auth::login خودش session را migrate می‌کند — regenerate دوباره نکن
     * (قفل/از‌دست‌رفتن داده روی درایورهای سشن پروداکشن).
     * password_hash را برای Filament AuthenticateSession همگام کن؛ password تهی امن است.
     */
    protected function syncPasswordHashForAuthenticateSession(User $user): void
    {
        $session = request()->session();
        $guard = Auth::getDefaultDriver();

        // هم‌ارز AuthenticateSession؛ password تهی (کاربر OTP) هم امن است
        $password = $user->getAuthPassword();
        $password = is_string($password) ? $password : '';

        $session->put('password_hash_'.$guard, $password);
        $session->put('password_hash_sha256_'.$guard, hash('sha256', $password));
    }

    protected function ensureSoftDeletesSchemaQuietly(): void
    {
        try {
            app(SoftDeletesSchemaRepair::class)->ensureOnce();
        } catch (\Throwable $e) {
            try {
                Log::warning('admin.impersonation.soft_deletes_repair_skipped', [
                    'message' => $e->getMessage(),
                ]);
            } catch (\Throwable) {
            }
        }
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

    /**
     * دقیقاً یکی از نقش‌های مجاز برای impersonation.
     *
     * @return null|'sales_manager'|'bankdar'|'representative'
     */
    public function resolveTargetRole(User $target): ?string
    {
        $flags = [
            'sales_manager' => $target->isSalesManager(),
            'bankdar' => $target->isBankdar(),
            'representative' => $target->isRepresentative(),
        ];

        $active = array_keys(array_filter($flags));

        return count($active) === 1 ? $active[0] : null;
    }

    protected function isTrashedSafe(User $user): bool
    {
        try {
            return $user->trashed();
        } catch (\Throwable) {
            return false;
        }
    }
}
