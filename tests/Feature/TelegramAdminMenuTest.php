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

        $user = User::factory()->create([
            'telegram_id' => $adminTelegramId,
            'email' => null,
        ]);

        $group = MuscleGroup::query()->where('name', 'Грудь')->firstOrFail();
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
                'data' => 'settings:admin',
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
                'data' => 'settings:admin_group:'.$group->id,
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
                'data' => 'settings:admin_add:'.$group->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 7005,
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
            'update_id' => 7006,
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
                'data' => 'settings:admin_toggle:'.$group->id.':'.$exercise->id,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('exercises', [
            'id' => $exercise->id,
            'is_active' => false,
        ]);
    }
}
