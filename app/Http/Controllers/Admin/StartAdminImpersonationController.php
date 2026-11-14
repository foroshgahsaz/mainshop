<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\Auth\AdminImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * شروع ورود موقت خارج از Livewire — تا Auth::login وسط livewire.update باعث ۵۰۰ نشود.
 */
class StartAdminImpersonationController
{
    public function __invoke(Request $request, User $user, AdminImpersonationService $impersonation): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        abort_unless($impersonation->canStart($actor, $user), 403);

        try {
            return redirect()->to($impersonation->start($actor, $user));
        } catch (Throwable $e) {
            Log::error('admin.impersonation.start_failed', [
                'actor_id' => (int) $actor->getKey(),
                'target_id' => (int) $user->getKey(),
                'message' => $e->getMessage(),
                'exception' => $e::class,
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            throw $e;
        }
    }
}
