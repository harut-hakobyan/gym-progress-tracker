<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Enums\WorkoutStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramExerciseMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_attach_photo_media_to_exercise(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $adminTelegramId = 991001;
        config(['telegram.admin_ids' => [$adminTelegramId]]);

        User::factory()->create([
            'telegram_id' => $adminTelegramId,
            'email' => null,
        ]);

        $exercise = Exercise::query()->where('is_active', true)->firstOrFail();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8101,
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
                'data' => 'admin:menu',
            ],
        ])->assertOk();

        $groupId = (int) $exercise->muscle_group_id;

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8102,
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
                'data' => 'admin:groups',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8103,
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
                'data' => 'admin:group:'.$groupId,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8104,
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
                'data' => 'admin:media:'.$groupId.':'.$exercise->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8105,
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
                'data' => 'admin:media_kind:'.$groupId.':'.$exercise->id.':photo',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8106,
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
                'photo' => [
                    [
                        'file_id' => 'small-photo',
                        'file_unique_id' => 'small-photo-unique',
                        'width' => 100,
                        'height' => 100,
                    ],
                    [
                        'file_id' => 'photo-file-123',
                        'file_unique_id' => 'photo-file-unique',
                        'width' => 800,
                        'height' => 800,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('exercises', [
            'id' => $exercise->id,
            'media_type' => 'photo',
            'media_value' => 'photo-file-123',
        ]);
    }

    public function test_user_sees_exercise_media_in_workout_card(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $telegramId = 991002;
        $user = User::factory()->create([
            'telegram_id' => $telegramId,
            'email' => null,
        ]);

        $exercise = Exercise::query()->where('is_active', true)->firstOrFail();
        $exercise->update([
            'media_type' => 'animation',
            'media_value' => 'gif-file-123',
        ]);

        Workout::query()->create([
            'user_id' => $user->id,
            'workout_template_id' => null,
            'name' => 'Workout',
            'status' => WorkoutStatus::Active,
            'started_at' => now(),
            'completed_at' => null,
            'duration_seconds' => null,
            'user_body_weight' => null,
            'notes' => null,
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8201,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'User',
                    'username' => 'user',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'workout:exercise:'.$exercise->id,
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'editMessageMedia')
                && str_contains((string) $request['media'], 'gif-file-123')
                && str_contains((string) $request['media'], 'animation');
        });
    }

    public function test_user_can_go_back_from_media_exercise_screen(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $telegramId = 991003;
        $user = User::factory()->create([
            'telegram_id' => $telegramId,
            'email' => null,
        ]);

        $exercise = Exercise::query()->where('is_active', true)->firstOrFail();
        $exercise->update([
            'media_type' => 'photo',
            'media_value' => 'photo-file-xyz',
        ]);

        Workout::query()->create([
            'user_id' => $user->id,
            'workout_template_id' => null,
            'name' => 'Workout',
            'status' => WorkoutStatus::Active,
            'started_at' => now(),
            'completed_at' => null,
            'duration_seconds' => null,
            'user_body_weight' => null,
            'notes' => null,
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8301,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'User',
                    'username' => 'user',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'workout:exercise:'.$exercise->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 8302,
            'callback_query' => [
                'id' => 'cb-2',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'User',
                    'username' => 'user',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                    'photo' => [
                        [
                            'file_id' => 'small-photo',
                            'file_unique_id' => 'small-photo-unique',
                            'width' => 100,
                            'height' => 100,
                        ],
                    ],
                ],
                'data' => 'exercise:back:current',
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'sendMessage')
                && str_contains((string) $request['text'], 'Активная тренировка');
        });
    }
}
