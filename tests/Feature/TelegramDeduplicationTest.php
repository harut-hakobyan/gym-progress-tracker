<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_update_is_ignored(): void
    {
        Http::fake([
            'api.telegram.org/*sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $payload = [
            'update_id' => 2002,
            'message' => [
                'message_id' => 11,
                'from' => [
                    'id' => 555222,
                    'first_name' => 'Ivan',
                ],
                'chat' => [
                    'id' => 555222,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ];

        $this->postJson('/api/telegram/webhook/test-secret', $payload)->assertOk();
        $this->postJson('/api/telegram/webhook/test-secret', $payload)->assertOk();

        Http::assertSentCount(1);
    }
}
