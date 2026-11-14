<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\Auth\AdminImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * شروع ورود موقت خارج از Livewire — تا Auth::login + session regenerate
 * وسط درخواست Filament/Livewire باعث ۵۰۰ نشود.
 */
class StartAdminImpersonationController
{
    public function __invoke(Request $request, User $user, AdminImpersonationService $impersonation): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        abort_unless($impersonation->canStart($actor, $user), 403);

        return redirect()->to($impersonation->start($actor, $user));
    }
}
