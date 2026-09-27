<?php

namespace Tests\Unit;

use App\Services\Settings\TransactionalSmsSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionalSmsSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_custom_order_paid_template(): void
    {
        $service = app(TransactionalSmsSettingsService::class);
        $rows = $service->forAdminForm();

        foreach ($rows as &$row) {
            if ($row['key'] === 'order_paid') {
                $row['body'] = 'پرداخت {order_code} با {gateway}';
                $row['enabled'] = true;
            }
        }
        unset($row);

        $service->saveFromAdminForm($rows);

        $template = app(TransactionalSmsSettingsService::class)->template('order_paid');

        $this->assertTrue($template['enabled']);
        $this->assertSame('پرداخت {order_code} با {gateway}', $template['body']);
    }
}
