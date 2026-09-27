<?php

namespace App\Services\Sms;

class SmsTemplateRenderer
{
    /** @param  array<string, string>  $variables */
    public function render(string $template, array $variables): string
    {
        $message = $template;

        foreach ($variables as $key => $value) {
            $message = str_replace('{'.$key.'}', $value, $message);
        }

        return trim(preg_replace('/\s+/u', ' ', $message) ?? $message);
    }
}
