<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAdminMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_exercises_by_muscle_group_in_telegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $adminTelegramId = 990001;
        config(['telegram.admin_ids' => [$adminTelegramId]]);

        User::factory()->create([
            'telegram_id' => $adminTelegramId,
            'email' => null,
        ]);

        $group = MuscleGroup::query()
            ->whereHas('exercises')
            ->orderBy('name')
            ->firstOrFail();

        $exercise = Exercise::query()
            ->where('muscle_group_id', $group->id)
            ->where('is_active', true)
            ->firstOrFail();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7001,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => [
                    'id' => $adminTelegramId,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $adminTelegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'settings:main',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7002,
            'callback_query' => [
                'id' => 'cb-2',
                'from' => [
                    'id' => $adminTelegramId,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $adminTelegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'admin:menu',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7003,
            'callback_query' => [
                'id' => 'cb-3',
                'from' => [
                    'id' => $adminTelegramId,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $adminTelegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'admin:groups',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7004,
            'callback_query' => [
                'id' => 'cb-4',
                'from' => [
                    'id' => $adminTelegramId,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $adminTelegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'admin:group:'.$group->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7005,
            'callback_query' => [
                'id' => 'cb-5',
                'from' => [
                    'id' => $adminTelegramId,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $adminTelegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'admin:add:'.$group->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7006,
            'message' => [
                'message_id' => 11,
                'from' => [
                    'id' => $adminTelegramId,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'chat' => [
                    'id' => $adminTelegramId,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => 'Admin Press',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('exercises', [
            'user_id' => null,
            'muscle_group_id' => $group->id,
            'name' => 'Admin Press',
            'is_active' => true,
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7007,
            'callback_query' => [
                'id' => 'cb-6',
                'from' => [
                    'id' => $adminTelegramId,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $adminTelegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'admin:toggle:'.$group->id.':'.$exercise->id,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('exercises', [
            'id' => $exercise->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_view_users_list_in_telegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $adminTelegramId = 990002;
        config(['telegram.admin_ids' => [$adminTelegramId]]);

        User::factory()->create([
            'telegram_id' => $adminTelegramId,
            'email' => null,
        ]);

        User::factory()->create([
            'name' => 'Alice User',
            'telegram_id' => 990003,
            'telegram_username' => 'alice',
            'email' => null,
            'preferred_language' => 'en',
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7010,
            'callback_query' => [
                'id' => 'cb-users',
                'from' => [
                    'id' => $adminTelegramId,
                    'first_name' => 'Admin',
                    'username' => 'admin',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $adminTelegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'admin:users',
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'editMessageText')
                && str_contains((string) $request['text'], __('telegram.admin.users_title'))
                && str_contains((string) $request['text'], 'Alice User');
        });
    }
}
