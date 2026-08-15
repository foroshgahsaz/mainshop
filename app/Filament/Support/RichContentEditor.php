<?php

namespace App\Filament\Support;

use Filament\Forms\Components\RichEditor;

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

    public static function make(string $name, ?string $label = null, string $attachmentsDirectory = 'content'): RichEditor
    {
        return RichEditor::make($name)
            ->label($label ?? 'محتوا')
            ->toolbarButtons(self::TOOLBAR)
            ->fileAttachmentsDirectory($attachmentsDirectory)
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->columnSpanFull();
    }
}
