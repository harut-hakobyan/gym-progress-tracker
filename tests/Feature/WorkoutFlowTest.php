<?php

namespace Tests\Feature;

use App\Enums\WorkoutStatus;
use App\Models\Exercise;
use App\Models\WorkoutTemplate;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WorkoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_workout_flow_can_start_add_set_and_complete_workout(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $telegramId = 880001;
        $startPayload = [
            'update_id' => 3001,
            'message' => [
                'message_id' => 10,
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
                'text' => '/workout',
            ],
        ];

        $this->postJson('/api/telegram/webhook/test-secret', $startPayload)->assertOk();

        $exercise = Exercise::query()->where('name', 'Жим штанги лёжа')->firstOrFail();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 3002,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'workout:template:empty',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 3003,
            'callback_query' => [
                'id' => 'cb-2',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
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

        $workoutExercise = WorkoutExercise::query()->firstOrFail();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 3004,
            'callback_query' => [
                'id' => 'cb-3',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'set:add:'.$workoutExercise->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 3005,
            'message' => [
                'message_id' => 11,
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
                'text' => '82.5',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 3006,
            'message' => [
                'message_id' => 12,
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
                'text' => '8',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 3007,
            'callback_query' => [
                'id' => 'cb-4',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'workout:complete:current',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('workouts', [
            'user_id' => 1,
            'status' => WorkoutStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('workout_sets', [
            'workout_exercise_id' => $workoutExercise->id,
            'set_number' => 1,
            'weight' => '82.50',
            'repetitions' => 8,
        ]);
    }

    public function test_repeat_set_reuses_previous_weight_and_repetitions(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $telegramId = 880002;
        $exercise = Exercise::query()->where('is_active', true)->firstOrFail();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4001,
            'message' => [
                'message_id' => 10,
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
                'text' => '/workout',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4002,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'workout:template:empty',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4003,
            'callback_query' => [
                'id' => 'cb-2',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
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

        $workoutExercise = WorkoutExercise::query()->firstOrFail();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4004,
            'callback_query' => [
                'id' => 'cb-3',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'set:add:'.$workoutExercise->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4005,
            'message' => [
                'message_id' => 11,
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
                'text' => '80',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4006,
            'message' => [
                'message_id' => 12,
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
                'text' => '6',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4007,
            'callback_query' => [
                'id' => 'cb-4',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'set:repeat:'.$workoutExercise->id,
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'editMessageText')
                && str_contains((string) $request['text'], 'Подход №2')
                && str_contains((string) $request['text'], 'Всего подходов в упражнении: 2');
        });

        $this->assertDatabaseHas('workout_sets', [
            'workout_exercise_id' => $workoutExercise->id,
            'set_number' => 1,
            'weight' => '80.00',
            'repetitions' => 6,
            'rpe' => null,
        ]);

        $this->assertDatabaseHas('workout_sets', [
            'workout_exercise_id' => $workoutExercise->id,
            'set_number' => 2,
            'weight' => '80.00',
            'repetitions' => 6,
            'rpe' => null,
        ]);
    }

    public function test_workout_start_shows_custom_templates_before_standard_ones(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $telegramId = 880003;
        $user = \App\Models\User::query()->create([
            'telegram_id' => $telegramId,
            'name' => 'Harut',
            'email' => null,
            'telegram_username' => 'harut',
            'preferred_language' => 'ru',
            'timezone' => 'Asia/Yerevan',
            'weight_unit' => 'kg',
        ]);

        WorkoutTemplate::factory()->forUser($user)->create([
            'name' => 'Мой шаблон',
            'is_active' => true,
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4010,
            'message' => [
                'message_id' => 10,
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
                'text' => '/workout',
            ],
        ])->assertOk();

        Http::assertSentCount(1);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'sendMessage')
                && str_contains((string) $request['text'], 'Выберите шаблон тренировки')
                && str_contains((string) $request['reply_markup'], 'Мой шаблон')
                && str_contains((string) $request['reply_markup'], 'workout:templates:standard');
        });

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4011,
            'callback_query' => [
                'id' => 'cb-1',
                'from' => [
                    'id' => $telegramId,
                    'first_name' => 'Harut',
                    'username' => 'harut',
                ],
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'workout:templates:standard',
            ],
        ])->assertOk();

        Http::assertSentCount(3);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'editMessageText')
                && str_contains((string) $request['text'], 'Стандартные шаблоны')
                && str_contains((string) $request['reply_markup'], 'Full Body')
                && str_contains((string) $request['reply_markup'], 'workout:templates:custom');
        });
    }
}
