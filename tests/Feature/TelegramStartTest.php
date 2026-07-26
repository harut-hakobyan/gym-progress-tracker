<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramStartTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_command_registers_user_and_sends_main_menu(): void
    {
        Http::fake([
            'api.telegram.org/*sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $payload = [
            'update_id' => 1001,
            'message' => [
                'message_id' => 10,
                'from' => [
                    'id' => 555111,
                    'first_name' => 'Harut',
                    'last_name' => 'Mkrtchyan',
                    'username' => 'harut',
                ],
                'chat' => [
                    'id' => 555111,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook/test-secret', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'telegram_id' => 555111,
            'name' => 'Harut Mkrtchyan',
            'telegram_username' => 'harut',
            'preferred_language' => 'ru',
            'timezone' => 'Asia/Yerevan',
            'weight_unit' => 'kg',
        ]);

        $this->assertDatabaseHas('processed_telegram_updates', [
            'update_id' => 1001,
            'status' => 'processed',
        ]);

        Http::assertSentCount(1);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'sendMessage')
                && (int) $request['chat_id'] === 555111
                && (string) $request['text'] !== '';
        });
    }

    public function test_start_command_with_bot_username_is_normalized(): void
    {
        Config::set('telegram.bot_username', 'gym_progress_tracker_pro_bot');

        Http::fake([
            'api.telegram.org/*sendMessage' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $payload = [
            'update_id' => 1002,
            'message' => [
                'message_id' => 11,
                'from' => [
                    'id' => 555222,
                    'first_name' => 'Test',
                    'username' => 'test_user',
                ],
                'chat' => [
                    'id' => 555222,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start@gym_progress_tracker_pro_bot',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook/test-secret', $payload);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'telegram_id' => 555222,
            'name' => 'Test',
            'telegram_username' => 'test_user',
        ]);

        $this->assertDatabaseHas('processed_telegram_updates', [
            'update_id' => 1002,
            'status' => 'processed',
        ]);

        Http::assertSentCount(1);
    }

    public function test_start_command_reuses_the_existing_main_menu_message(): void
    {
        Http::fake([
            'api.telegram.org/*sendMessage' => Http::sequence()
                ->push([
                    'ok' => true,
                    'result' => [
                        'message_id' => 200,
                    ],
                ], 200)
                ->push([
                    'ok' => true,
                    'result' => [
                        'message_id' => 201,
                    ],
                ], 200),
            'api.telegram.org/*deleteMessage' => Http::response([
                'ok' => true,
            ], 200),
        ]);

        $payload = [
            'update_id' => 1003,
            'message' => [
                'message_id' => 12,
                'from' => [
                    'id' => 555333,
                    'first_name' => 'Harut',
                    'last_name' => 'Mkrtchyan',
                    'username' => 'harut',
                ],
                'chat' => [
                    'id' => 555333,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ];

        $response = $this->postJson('/api/telegram/webhook/test-secret', $payload);
        $response->assertOk();

        $payload['update_id'] = 1004;
        $payload['message']['message_id'] = 13;

        $response = $this->postJson('/api/telegram/webhook/test-secret', $payload);
        $response->assertOk();

        Http::assertSentCount(3);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'sendMessage');
        });

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'deleteMessage')
                && (int) $request['message_id'] === 200;
        });
    }
}
