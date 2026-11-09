<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Services\Settings\ShopAssetSettingsService;
use App\Services\Shop\ShopAssetMinifier;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ManageShopAssetSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'فشرده‌سازی CSS/JS';

    protected static ?string $slug = 'shop-asset-settings';

    protected static ?string $title = 'فشرده‌سازی فایل‌های فروشگاه';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(ShopAssetSettingsService $assets): void
    {
        $this->form->fill($assets->forAdminForm());
    }

    public function form(Form $form): Form
    {
        $serveFields = [];

        foreach (ShopAssetSettingsService::ASSET_DEFINITIONS as $key => $definition) {
            $minPath = preg_replace('/\.(css|js)$/i', '.min.$1', $definition['path']);
            $exists = is_file(public_path($minPath));

            $serveFields[] = Forms\Components\Toggle::make('serve.'.$key)
                ->label($definition['label'])
                ->helperText($exists
                    ? "فایل {$minPath} موجود است."
                    : 'ابتدا «ساخت فایل‌های فشرده» را بزنید؛ تا آن زمان نسخهٔ اصلی لود می‌شود.')
                ->inline(false)
                ->disabled(fn (callable $get) => ! (bool) $get('enabled'));
        }

        return $form
            ->schema([
                Forms\Components\Section::make('استفاده از فایل‌های فشرده در فروشگاه')
                    ->description('فقط فاصله و کامنت حذف می‌شود؛ رنگ‌ها و layout تغییر نمی‌کند. اگر فایل .min نباشد، همان نسخهٔ اصلی نمایش داده می‌شود.')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('فعال‌سازی سرو فایل‌های min در فروشگاه')
                            ->default(false)
                            ->inline(false),
                        Forms\Components\Fieldset::make('کدام فایل‌ها با نسخهٔ min لود شوند؟')
                            ->schema($serveFields),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buildMinified')
                ->label('ساخت فایل‌های فشرده')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('ساخت/به‌روزرسانی .min.css و .min.js')
                ->modalDescription('از روی فایل‌های فعلی public/shop ساخته می‌شود. بعد از تغییر CSS/JS در کد، دوباره این دکمه را بزنید.')
                ->action(function (ShopAssetMinifier $minifier): void {
                    $results = $minifier->build();

                    if ($results === []) {
                        Notification::make()
                            ->warning()
                            ->title('فایلی برای فشرده‌سازی پیدا نشد')
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('فایل‌های فشرده ساخته شد')
                        ->body(count($results).' فایل به‌روز شد. گزینه‌های بالا را ذخیره کنید.')
                        ->send();

                    $this->mount(app(ShopAssetSettingsService::class));
                }),
        ];
    }

    public function save(ShopAssetSettingsService $assets): void
    {
        $data = $this->form->getState();
        $assets->saveFromAdminForm($data);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات فشرده‌سازی فروشگاه ذخیره شد.')
            ->send();
    }
}
