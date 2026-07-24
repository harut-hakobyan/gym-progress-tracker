<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramGoalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_creation_flow_works_in_telegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $telegramId = 991201;

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6001,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'chat' => [
                    'id' => $telegramId,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/goals',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6002,
            'callback_query' => [
                'id' => 'cb-goals-1',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 1,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'goals:create',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6003,
            'callback_query' => [
                'id' => 'cb-goals-2',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 1,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'goals:type:weekly_workouts',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6004,
            'message' => [
                'message_id' => 2,
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'chat' => [
                    'id' => $telegramId,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '3',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6005,
            'message' => [
                'message_id' => 3,
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'chat' => [
                    'id' => $telegramId,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => 'skip',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('user_goals', [
            'user_id' => 1,
            'type' => 'weekly_workouts',
            'target_value' => '3.00',
            'status' => 'active',
        ]);
    }
}
