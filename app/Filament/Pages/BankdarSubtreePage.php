<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\Representative\BankdarSubtreeService;
use App\Support\AdminAccess;
use Filament\Actions;
use Filament\Pages\Page;

class BankdarSubtreePage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'bankdars/{record}/subtree';

    protected static string $view = 'filament.pages.bankdar-subtree';

    protected static ?string $title = 'زیرمجموعه بنکدار';

    public User $bankdar;

    /** @var array<string, mixed> */
    public array $tree = [];

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function mount(int|string $record): void
    {
        $this->bankdar = User::query()
            ->where('is_bankdar', true)
            ->findOrFail($record);

        $this->tree = app(BankdarSubtreeService::class)->treeForBankdar($this->bankdar);
    }

    public function getTitle(): string
    {
        return 'زیرمجموعه: '.$this->bankdar->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('بازگشت به بنکدارها')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->url(UserResource::getUrl('index', [
                    'activeTab' => User::ADMIN_LIST_SEGMENT_BANKDAR,
                ])),
            Actions\Action::make('editBankdar')
                ->label('ویرایش بنکدار')
                ->icon('heroicon-o-pencil-square')
                ->url(UserResource::getUrl('edit', ['record' => $this->bankdar])),
        ];
    }
}
