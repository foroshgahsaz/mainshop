<?php

namespace App\Jobs;

use App\Contracts\SmsSender;
use App\Services\Settings\TransactionalSmsSettingsService;
use App\Services\Sms\SmsTemplateRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTransactionalSmsJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    /** @param  array<string, string>  $variables */
    public function __construct(
        public string $templateKey,
        public string $phone,
        public array $variables = [],
    ) {}

    public function handle(
        SmsSender $sms,
        TransactionalSmsSettingsService $settings,
        SmsTemplateRenderer $renderer,
    ): void {
        $template = $settings->template($this->templateKey);

        if ($template === null || ! $template['enabled'] || $template['body'] === '') {
            return;
        }

        $phone = preg_replace('/\D+/', '', $this->phone) ?? '';

        if (! preg_match('/^09\d{9}$/', $phone)) {
            return;
        }

        $message = $renderer->render($template['body'], $this->variables);

        if ($message === '') {
            return;
        }

        try {
            $sms->sendTransactional($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('Transactional SMS job failed', [
                'template' => $this->templateKey,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
