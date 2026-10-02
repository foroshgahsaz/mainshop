<?php

namespace Tests\Unit;

use App\Contracts\SmsSender;
use App\Jobs\SendTransactionalSmsJob;
use App\Services\Settings\TransactionalSmsSettingsService;
use App\Services\Sms\SmsTemplateRenderer;
use Mockery;
use Tests\TestCase;

class SendTransactionalSmsJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_sends_rendered_template_when_enabled(): void
    {
        $this->expectNotToPerformAssertions();

        $settings = Mockery::mock(TransactionalSmsSettingsService::class);
        $settings->shouldReceive('template')
            ->with('order_paid')
            ->andReturn(['enabled' => true, 'body' => 'پرداخت {order_code}']);

        $sms = Mockery::mock(SmsSender::class);
        $sms->shouldReceive('sendTransactional')
            ->once()
            ->with('09121234567', 'پرداخت ABC123');

        $job = new SendTransactionalSmsJob('order_paid', '09121234567', [
            'order_code' => 'ABC123',
        ]);

        $job->handle($sms, $settings, new SmsTemplateRenderer);
    }

    public function test_it_skips_when_template_disabled(): void
    {
        $this->expectNotToPerformAssertions();

        $settings = Mockery::mock(TransactionalSmsSettingsService::class);
        $settings->shouldReceive('template')
            ->with('order_paid')
            ->andReturn(['enabled' => false, 'body' => 'ignored']);

        $sms = Mockery::mock(SmsSender::class);
        $sms->shouldNotReceive('sendTransactional');

        $job = new SendTransactionalSmsJob('order_paid', '09121234567', []);
        $job->handle($sms, $settings, new SmsTemplateRenderer);
    }
}
