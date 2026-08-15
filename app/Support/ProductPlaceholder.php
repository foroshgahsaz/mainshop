<?php

namespace App\Support;

class ProductPlaceholder
{
    public static function ensure(string $relativePath, string $label = ''): void
    {
        $fullPath = storage_path('app/public/'.$relativePath);

        if (is_file($fullPath) && filesize($fullPath) >= 512) {
            return;
        }

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $template = self::findValidTemplate();

        if ($template) {
            copy($template, $fullPath);

            return;
        }

        if (function_exists('imagecreatetruecolor')) {
            self::writeWithGd($fullPath, $label);

            return;
        }

        file_put_contents($fullPath, self::minimalJpeg());
    }

    protected static function findValidTemplate(): ?string
    {
        $directory = storage_path('app/public/products');

        if (! is_dir($directory)) {
            return null;
        }

        foreach (glob($directory.'/*.{jpg,jpeg,webp,png}', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file) && filesize($file) > 5000) {
                return $file;
            }
        }

        return null;
    }

    protected static function writeWithGd(string $fullPath, string $label): void
    {
        $width = 900;
        $height = 900;
        $image = imagecreatetruecolor($width, $height);
        $background = imagecolorallocate($image, 245, 248, 250);
        $accent = imagecolorallocate($image, 114, 57, 234);
        $textColor = imagecolorallocate($image, 62, 66, 84);

        imagefilledrectangle($image, 0, 0, $width, $height, $background);
        imagerectangle($image, 40, 40, $width - 41, $height - 41, $accent);

        if ($label !== '') {
            $truncated = mb_strlen($label) > 28 ? mb_substr($label, 0, 28).'…' : $label;
            imagestring($image, 5, 80, (int) ($height / 2) - 10, $truncated, $textColor);
        }

        imagejpeg($image, $fullPath, 88);
        imagedestroy($image);
    }

    protected static function minimalJpeg(): string
    {
        return base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP/bAEMBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAAKAAoDASIAAhEBAxEB/8QAFgABAQEAAAAAAAAAAAAAAAAAAAYH/8QAIRAAAgICAgIDAAAAAAAAAAAAAAECAwQRAAUSITET/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
            true
        ) ?: '';
    }
}
