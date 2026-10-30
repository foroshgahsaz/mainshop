<?php

namespace Tests\Feature;

use App\Filament\Forms\Components\MediaPicker;
use App\Filament\Support\ShopIconUpload;
use App\Filament\Support\ShopMediaPicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class ShopImageUploadFieldTest extends TestCase
{
    public function test_shop_media_picker_renders_media_center_button(): void
    {
        $html = Livewire::test(ShopImageUploadFormStub::class)->html();

        $this->assertStringContainsString('media-picker-field', $html);
        $this->assertStringContainsString('انتخاب / تغییر تصویر', $html);
        $this->assertStringContainsString('openMediaCenter', $html);
        $this->assertStringContainsString('بدون تصویر', $html);
    }

    public function test_shop_media_picker_preview_renders_selected_path(): void
    {
        $html = Livewire::test(ShopImageUploadFormStub::class)
            ->set('data.image', 'products/laptop.webp')
            ->html();

        $this->assertStringContainsString('products/laptop.webp', $html);
        $this->assertStringContainsString('حذف تصویر', $html);
        $this->assertStringNotContainsString('بدون تصویر', $html);
    }

    public function test_repeater_media_pickers_have_distinct_action_names(): void
    {
        $html = Livewire::test(ShopRepeaterMediaPickerFormStub::class)->html();

        preg_match_all('/openMediaCenter_[a-f0-9]{8}/', $html, $matches);

        $this->assertGreaterThanOrEqual(2, count($matches[0] ?? []));
        $this->assertSame(count($matches[0]), count(array_unique($matches[0])));
    }

    public function test_shop_icon_upload_uses_native_upload_view(): void
    {
        $this->assertSame(
            'filament.forms.components.shop-image-upload',
            ShopIconUpload::make('icon', 'shipping')->getView(),
        );
    }
}

class ShopRepeaterMediaPickerFormStub extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'items' => [
                ['label' => 'اول', 'image' => null],
                ['label' => 'دوم', 'image' => null],
            ],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('items')
                    ->schema([
                        TextInput::make('label')->required(),
                        ShopMediaPicker::image('image', 'products', 'تصویر'),
                    ]),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>{{ $this->form }}</div>
        HTML;
    }
}

class ShopImageUploadFormStub extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ShopMediaPicker::image('image', 'products', 'تصویر'),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>{{ $this->form }}</div>
        HTML;
    }
}
