<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramLanguageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_language_is_initialized_from_telegram_language_code(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $payload = [
            'update_id' => 8101,
            'message' => [
                'message_id' => 10,
                'from' => [
                    'id' => 555310,
                    'first_name' => 'Aram',
                    'last_name' => 'Petrosyan',
                    'username' => 'aram',
                    'language_code' => 'hy',
                ],
                'chat' => [
                    'id' => 555310,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ];

        $this->postJson('/api/telegram/webhook/test-secret', $payload)->assertOk();

        $this->assertDatabaseHas('users', [
            'telegram_id' => 555310,
            'preferred_language' => 'hy',
        ]);
    }

    public function test_settings_menu_can_switch_the_user_language(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $telegramId = 555311;

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8102,
            'message' => [
                'message_id' => 11,
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Narek',
                    'last_name' => 'Minasyan',
                    'username' => 'narek',
                    'language_code' => 'en',
                ],
                'chat' => [
                    'id' => $telegramId,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/start',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8103,
            'callback_query' => [
                'id' => 'cb-settings-language',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Narek',
                    'username' => 'narek',
                    'language_code' => 'en',
                ],
                'message' => [
                    'message_id' => 11,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'settings:language:hy',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'telegram_id' => $telegramId,
            'preferred_language' => 'hy',
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8104,
            'callback_query' => [
                'id' => 'cb-settings-main',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Narek',
                    'username' => 'narek',
                    'language_code' => 'en',
                ],
                'message' => [
                    'message_id' => 11,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'settings:main',
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'editMessageText')
                && str_contains((string) $request['text'], 'Կարգավորումներ')
                && str_contains((string) $request['text'], 'Ընթացիկ լեզու');
        });
    }
}
