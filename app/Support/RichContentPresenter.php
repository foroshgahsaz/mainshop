<?php

namespace App\Support;

final class RichContentPresenter
{
    /**
     * @return array{videos: string, body: string}
     */
    public static function splitVideos(?string $html): array
    {
        $html = trim((string) $html);
        if ($html === '') {
            return ['videos' => '', 'body' => ''];
        }

        $videos = [];
        $patterns = [
            '/<figure\b[^>]*\brich-content-video\b[^>]*>.*?<\/figure>/is',
            '/<figure\b[^>]*\bdata-rich-content-video\b[^>]*>.*?<\/figure>/is',
        ];

        $body = $html;
        foreach ($patterns as $pattern) {
            $body = preg_replace_callback($pattern, function (array $match) use (&$videos): string {
                $videos[] = $match[0];

                return '';
            }, $body) ?? $body;
        }

        $body = trim(preg_replace("/\n{3,}/", "\n\n", $body) ?? $body);

        return [
            'videos' => implode("\n", $videos),
            'body' => $body,
        ];
    }
}
