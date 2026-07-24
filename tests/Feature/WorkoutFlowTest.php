<?php

namespace Tests\Feature;

use App\Enums\WorkoutStatus;
use App\Models\Exercise;
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
                'data' => 'set:rpe:skip:'.$workoutExercise->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 3008,
            'callback_query' => [
                'id' => 'cb-5',
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
            'weight' => '82.50',
            'repetitions' => 8,
        ]);
    }
}
