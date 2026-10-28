<?php

namespace Tests\Unit;

use App\Services\Payment\PaymentLogTimelineParser;
use Tests\TestCase;

class PaymentLogTimelineParserTest extends TestCase
{
    public function test_parses_log_line_with_gateway_raw(): void
    {
        $line = '[2026-10-26 08:15:02] production.INFO: [35%] callback_received: بازگشت کاربر از درگاه به سایت. '
            .'{"payment_id":149,"tracking_code":"PAY-ABC","order_id":90,"gateway":"parsian","status":"pending",'
            .'"code":"callback_received","progress":35,"context":{"authority":"tok1","status":"0","gateway_raw":{"Token":"tok1","status":"0","RRN":"123"}}}';

        $parser = new PaymentLogTimelineParser;
        $event = $parser->parseLine($line);

        $this->assertNotNull($event);
        $this->assertSame(149, $event['payment_id']);
        $this->assertSame('callback_received', $event['code']);
        $this->assertSame(35, $event['progress']);
        $this->assertTrue($event['has_gateway_payload']);
        $this->assertSame('tok1', $event['gateway_raw']['Token']);

        $display = $parser->formatGatewayPayloadForDisplay(
            $event['gateway_raw'],
            $event['context'],
            $event['code']
        );

        $this->assertStringContainsString('"Token": "tok1"', $display);
    }

    public function test_groups_by_payment_id(): void
    {
        $parser = new PaymentLogTimelineParser;
        $events = $parser->parseLines([
            '[2026-10-26 08:00:00] production.INFO: [10%] start: a {"payment_id":1,"progress":10,"context":{}}',
            '[2026-10-26 08:01:00] production.INFO: [20%] step: b {"payment_id":2,"progress":20,"context":{}}',
            '[2026-10-26 08:02:00] production.INFO: [30%] step: c {"payment_id":1,"progress":30,"context":{}}',
        ]);

        $groups = $parser->groupByPaymentId($events);

        $this->assertCount(2, $groups[1]);
        $this->assertCount(1, $groups[2]);
    }
}
