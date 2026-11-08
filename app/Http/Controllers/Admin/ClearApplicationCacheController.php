<?php

namespace App\Http\Controllers\Admin;

use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ClearApplicationCacheController
{
    /** @var list<string> */
    private const COMMANDS = [
        'cache:clear',
        'view:clear',
        'route:clear',
        'config:clear',
        'clear-compiled',
    ];

    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(AdminAccess::canManageShopInAdmin(), 403);

        $failed = [];

        foreach (self::COMMANDS as $command) {
            try {
                Artisan::call($command);
            } catch (\Throwable $e) {
                $failed[] = $command;
                report($e);
            }
        }

        if ($failed === []) {
            Notification::make()
                ->title('کش سیستم با موفقیت حذف شد')
                ->body('کش سیستم با موفقیت حذف شد')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('پاک‌سازی کش ناقص بود')
                ->body('برخی دستورها اجرا نشد: '.implode('، ', $failed))
                ->warning()
                ->send();
        }

        return redirect()->back();
    }
}
