<?php

namespace Tests\Unit;

use App\Services\Settings\SettingsService;
use App\Services\Settings\TransactionalSmsSettingsService;
use Mockery;
use Tests\TestCase;

class TransactionalSmsSettingsServiceTest extends TestCase
{
    /** @var array<string, string> */
    private array $store = [];

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeService(): TransactionalSmsSettingsService
    {
        $settings = Mockery::mock(SettingsService::class);
        $settings->shouldReceive('get')
            ->andReturnUsing(function (string $group, string $key, mixed $default = null): mixed {
                $id = "{$group}.{$key}";

                return array_key_exists($id, $this->store) ? $this->store[$id] : $default;
            });
        $settings->shouldReceive('set')
            ->andReturnUsing(function (string $group, string $key, mixed $value): void {
                $this->store["{$group}.{$key}"] = is_string($value) ? $value : (string) $value;
            });

        return new TransactionalSmsSettingsService($settings);
    }

    public function test_it_saves_custom_order_paid_template(): void
    {
        $service = $this->makeService();
        $rows = $service->forAdminForm();

        foreach ($rows as &$row) {
            if ($row['key'] === 'order_paid') {
                $row['body'] = 'پرداخت {order_code} با {gateway}';
                $row['enabled'] = true;
            }
        }
        unset($row);

        $service->saveFromAdminForm($rows);

        $template = $service->template('order_paid');

        $this->assertTrue($template['enabled']);
        $this->assertSame('پرداخت {order_code} با {gateway}', $template['body']);
    }

    public function test_it_persists_staff_phones(): void
    {
        $service = $this->makeService();
        $rows = $service->forAdminForm();

        $service->saveFromAdminForm($rows, "09121111111\n09122222222, 09123333333");

        $phones = $service->staffPhoneList();

        $this->assertSame(['09121111111', '09122222222', '09123333333'], $phones);
    }
}
