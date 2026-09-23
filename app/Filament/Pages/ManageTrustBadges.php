<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use App\Filament\Support\NormalizesFileUploadFormState;
use App\Filament\Support\ShopMediaPicker;
use App\Services\Media\MediaRegistry;
use App\Services\Settings\TrustBadgeService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;

class ManageTrustBadges extends Page implements HasForms
{
    use InteractsWithForms;
    use NormalizesFileUploadFormState {
        NormalizesFileUploadFormState::getFormUploadedFiles insteadof InteractsWithForms;
    }

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'مجوزها';

    protected static ?string $slug = 'trust-badges';

    protected static ?string $title = 'مجوزها و نمادهای اعتماد';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(TrustBadgeService $trustBadges): void
    {
        $this->form->fill([
            'badges' => $trustBadges->forAdminForm(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('نمادهای اعتماد فوتر')
                    ->description('مجوزها و نمادهایی مثل ای‌نماد، ترب و سایر موارد را اضافه کنید. برای کدهای آماده (ای‌نماد) نوع «کد» و برای تصویر با لینک، نوع «تصویر» را انتخاب کنید.')
                    ->schema([
                        Forms\Components\Repeater::make('badges')
                            ->label('مجوزها')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان')
                                    ->placeholder('مثلاً ای‌نماد، ترب، پرداخت امن')
                                    ->maxLength(120)
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->label('نوع نمایش')
                                    ->options([
                                        'code' => 'کد (ای‌نماد و ...)',
                                        'image' => 'تصویر',
                                    ])
                                    ->default('code')
                                    ->live()
                                    ->required(),
                                Forms\Components\Toggle::make('enabled')
                                    ->label('فعال')
                                    ->default(true)
                                    ->inline(false),
                                Forms\Components\Textarea::make('code')
                                    ->label('کد نماد')
                                    ->rows(6)
                                    ->columnSpanFull()
                                    ->helperText('کد HTML یا اسکریپت دریافتی از سامانه (مثل ای‌نماد) را اینجا قرار دهید.')
                                    ->visible(fn (Get $get): bool => $get('type') === 'code')
                                    ->required(fn (Get $get): bool => $get('type') === 'code'),
                                ShopMediaPicker::image('image', 'settings/trust-badges', 'تصویر نماد')
                                    ->visible(fn (Get $get): bool => $get('type') === 'image')
                                    ->required(fn (Get $get): bool => $get('type') === 'image'),
                                Forms\Components\TextInput::make('link')
                                    ->label('لینک')
                                    ->url()
                                    ->placeholder('https://...')
                                    ->helperText('اختیاری — با کلیک روی تصویر به این آدرس می‌رود.')
                                    ->visible(fn (Get $get): bool => $get('type') === 'image')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'مجوز جدید')
                            ->addActionLabel('افزودن مجوز')
                            ->reorderable()
                            ->defaultItems(0),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(TrustBadgeService $trustBadges, MediaRegistry $media): void
    {
        FileUploadSanitizer::sanitize($this, $this->form);

        $data = $this->form->getState();
        $items = $data['badges'] ?? [];

        $trustBadges->saveFromAdminForm($items);

        foreach ($items as $item) {
            if (($item['type'] ?? '') !== 'image') {
                continue;
            }

            $image = collect($item['image'] ?? [])
                ->first(fn (mixed $value): bool => is_string($value) && $value !== '');

            if (is_string($image) && $image !== '') {
                $media->registerFromPath('public', $image);
            }
        }

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('مجوزها و نمادهای اعتماد با موفقیت ذخیره شدند.')
            ->send();
    }
}
