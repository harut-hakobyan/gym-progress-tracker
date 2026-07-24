<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_invalid_secret(): void
    {
        Http::fake();

        $response = $this->postJson('/api/telegram/webhook/wrong-secret', [
            'update_id' => 9101,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 123456,
                    'first_name' => 'Test',
                ],
                'chat' => [
                    'id' => 123456,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('processed_telegram_updates', 0);
    }

    public function test_webhook_rejects_invalid_header_secret_when_present(): void
    {
        Http::fake();

        $response = $this
            ->withHeader('X-Telegram-Bot-Api-Secret-Token', 'bad-secret')
            ->postJson('/api/telegram/webhook/test-secret', [
                'update_id' => 9102,
                'message' => [
                    'message_id' => 1,
                    'from' => [
                        'id' => 123457,
                        'first_name' => 'Test',
                    ],
                    'chat' => [
                        'id' => 123457,
                        'type' => 'private',
                    ],
                    'date' => time(),
                    'text' => '/start',
                ],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('processed_telegram_updates', 0);
    }

    public function test_webhook_is_throttled(): void
    {
        Config::set('telegram.webhook_rate_limit_per_minute', 2);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $basePayload = [
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 123458,
                    'first_name' => 'Test',
                ],
                'chat' => [
                    'id' => 123458,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ];

        $this->postJson('/api/telegram/webhook/test-secret', $basePayload + ['update_id' => 9103])->assertOk();
        $this->postJson('/api/telegram/webhook/test-secret', $basePayload + ['update_id' => 9104])->assertOk();
        $this->postJson('/api/telegram/webhook/test-secret', $basePayload + ['update_id' => 9105])->assertStatus(429);
    }
}
