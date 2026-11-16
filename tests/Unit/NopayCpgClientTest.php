<?php

namespace Tests\Unit;

use App\Services\Payment\NopayCpgClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class NopayCpgClientTest extends TestCase
{
    public function test_non_json_body_throws_descriptive_invalid_response(): void
    {
        Http::fake([
            'https://op-cpg.test/*' => Http::response('<html>gateway</html>', 200, [
                'Content-Type' => 'text/html',
            ]),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('پاسخ نامعتبر از درگاه نوپی');

        app(NopayCpgClient::class)->call(
            'https://op-cpg.test',
            NopayCpgClient::SERVICE_REQUEST_TOKEN,
            ['amount' => 1000],
        );
    }

    public function test_json_object_is_returned(): void
    {
        Http::fake([
            'https://op-cpg.test/*' => Http::response([
                'result' => ['entity' => ['token' => 'abc']],
                'notification' => ['hasErrors' => false],
            ]),
        ]);

        $body = app(NopayCpgClient::class)->call(
            'https://op-cpg.test',
            NopayCpgClient::SERVICE_REQUEST_TOKEN,
            ['amount' => 1000],
        );

        $this->assertSame('abc', $body['result']['entity']['token']);
    }

    public function test_double_encoded_json_string_is_unwrapped(): void
    {
        $inner = [
            'result' => null,
            'notification' => [
                'errors' => [[
                    'message' => 'خطای احراز هویت',
                    'code' => -7,
                ]],
                'hasErrors' => true,
            ],
        ];

        Http::fake([
            'https://op-cpg.test/*' => Http::response(
                json_encode(json_encode($inner, JSON_UNESCAPED_UNICODE), JSON_UNESCAPED_UNICODE),
                200,
                ['Content-Type' => 'application/json'],
            ),
        ]);

        $client = app(NopayCpgClient::class);
        $body = $client->call(
            'https://op-cpg.test',
            NopayCpgClient::SERVICE_REQUEST_TOKEN,
            ['amount' => 1000],
        );

        $this->assertTrue($client->hasErrors($body));
        $this->assertSame('خطای احراز هویت (کد -7)', $client->firstErrorMessage($body));
    }
}
