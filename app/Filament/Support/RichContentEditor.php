<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;

class RichContentEditor
{
    /** @var list<string> */
    public const TOOLBAR = [
        'attachFiles',
        'blockquote',
        'bold',
        'bulletList',
        'codeBlock',
        'h2',
        'h3',
        'italic',
        'link',
        'orderedList',
        'redo',
        'strike',
        'underline',
        'undo',
    ];

    public static function make(string $name, ?string $label = null, string $attachmentsDirectory = 'content'): Group
    {
        return Group::make([
            self::editor($name, $label, $attachmentsDirectory),
            Actions::make([
                self::insertVideoAction($name, $attachmentsDirectory),
            ])->alignStart(),
        ])->columnSpanFull();
    }

    protected static function editor(string $name, ?string $label, string $attachmentsDirectory): RichEditor
    {
        return RichEditor::make($name)
            ->label($label ?? 'محتوا')
            ->toolbarButtons(self::TOOLBAR)
            ->fileAttachmentsDirectory($attachmentsDirectory)
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->columnSpanFull();
    }

    protected static function insertVideoAction(string $fieldName, string $attachmentsDirectory): Action
    {
        $videoDirectory = trim($attachmentsDirectory, '/').'/videos';
        $maxSizeKb = (int) config('shop.rich_content.video_max_size_kb', 102400);

        return Action::make('insertVideo_'.$fieldName)
            ->label('بارگذاری و درج ویدئو')
            ->icon('heroicon-o-video-camera')
            ->color('gray')
            ->modalHeading('درج ویدئو در محتوا')
            ->modalDescription('فایل ویدئو بارگذاری کنید یا آدرس مستقیم (URL) وارد کنید. ویدئو به انتهای همین محتوا اضافه می‌شود.')
            ->modalSubmitActionLabel('درج در محتوا')
            ->form([
                Tabs::make('video_source')->tabs([
                    Tabs\Tab::make('upload')->label('بارگذاری فایل')->schema([
                        FileUpload::make('video_file')
                            ->label('فایل ویدئو')
                            ->disk('public')
                            ->directory($videoDirectory)
                            ->acceptedFileTypes(RichContentVideo::ACCEPTED_MIMES)
                            ->maxSize($maxSizeKb)
                            ->helperText('فرمت‌های مجاز: MP4، WebM — حداکثر '.number_format($maxSizeKb).' کیلوبایت'),
                    ]),
                    Tabs\Tab::make('url')->label('آدرس اینترنتی')->schema([
                        TextInput::make('video_url')
                            ->label('URL ویدئو')
                            ->placeholder('https://...')
                            ->maxLength(2048),
                    ]),
                ]),
            ])
            ->action(function (array $data, Set $set, Get $get) use ($fieldName): void {
                $url = null;

                $path = Arr::first(Arr::wrap($data['video_file'] ?? null));

                if (is_string($path) && $path !== '') {
                    $url = RichContentVideo::urlFromUploadPath($path);
                }

                if ($url === null && filled($data['video_url'] ?? null)) {
                    $candidate = trim((string) $data['video_url']);
                    if (! RichContentVideo::isAllowedExternalUrl($candidate)) {
                        Notification::make()
                            ->title('آدرس ویدئو نامعتبر است')
                            ->danger()
                            ->send();

                        return;
                    }
                    $url = $candidate;
                }

                if ($url === null) {
                    Notification::make()
                        ->title('فایل یا آدرس ویدئو را وارد کنید')
                        ->warning()
                        ->send();

                    return;
                }

                $set($fieldName, RichContentVideo::appendToHtml($get($fieldName), $url));

                Notification::make()
                    ->title('ویدئو به محتوا اضافه شد')
                    ->success()
                    ->send();
            });
    }
}
