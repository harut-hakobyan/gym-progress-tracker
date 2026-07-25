<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\WorkoutTemplate;
use App\Models\WorkoutTemplateExercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramTemplateFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_template_by_selecting_groups_then_exercises(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $telegramId = 880003;

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5001,
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
                'text' => '/start',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5002,
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
                'data' => 'templates:list',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5003,
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
                'data' => 'templates:create',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5004,
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
                'text' => 'Upper Push',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5005,
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
                'data' => 'templates:day_create:1',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5006,
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
                'data' => 'templates:split:chest_triceps',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5007,
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
                'data' => 'templates:done',
            ],
        ])->assertOk();

        $chest = MuscleGroup::query()->where('name', 'Грудь')->firstOrFail();
        $triceps = MuscleGroup::query()->where('name', 'Трицепс')->firstOrFail();
        $selectedExercises = Exercise::query()
            ->whereIn('muscle_group_id', [$chest->id, $triceps->id])
            ->orderBy('name')
            ->limit(2)
            ->pluck('id')
            ->all();

        $this->assertNotEmpty($selectedExercises);

        foreach ($selectedExercises as $index => $exerciseId) {
            $this->postJson('/api/telegram/webhook/test-secret', [
                'update_id' => 5008 + $index,
                'callback_query' => [
                    'id' => 'cb-6'.$index,
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
                    'data' => 'templates:create_exercise:'.$exerciseId,
                ],
            ])->assertOk();
        }

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5010,
            'callback_query' => [
                'id' => 'cb-7',
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
                'data' => 'templates:done',
            ],
        ])->assertOk();

        $user = User::query()->where('telegram_id', $telegramId)->firstOrFail();
        $template = WorkoutTemplate::query()
            ->where('user_id', $user->id)
            ->where('name', 'Upper Push')
            ->with('templateExercises')
            ->firstOrFail();

        $this->assertCount(count($selectedExercises), $template->templateExercises);
        $this->assertSame(1, $template->day_of_week);

        foreach ($selectedExercises as $exerciseId) {
            $this->assertDatabaseHas('workout_template_exercises', [
                'workout_template_id' => $template->id,
                'exercise_id' => $exerciseId,
            ]);
        }
    }

    public function test_user_can_rename_edit_day_and_delete_own_template_in_telegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->create([
            'telegram_id' => 880010,
            'email' => null,
        ]);

        $template = WorkoutTemplate::factory()->forUser($user)->create([
            'name' => 'Old name',
            'description' => 'Old description',
            'is_active' => true,
        ]);

        $telegramId = 880010;

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6001,
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
                'data' => 'templates:view:'.$template->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6002,
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
                'data' => 'templates:edit:'.$template->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6003,
            'callback_query' => [
                'id' => 'cb-2b',
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
                'data' => 'templates:edit_name:'.$template->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6004,
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
                'text' => 'New name',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('workout_templates', [
            'id' => $template->id,
            'name' => 'New name',
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6005,
            'callback_query' => [
                'id' => 'cb-2c',
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
                'data' => 'templates:edit_day:'.$template->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6006,
            'callback_query' => [
                'id' => 'cb-2d',
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
                'data' => 'templates:day_edit:'.$template->id.':5',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('workout_templates', [
            'id' => $template->id,
            'day_of_week' => 5,
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6007,
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
                'data' => 'templates:delete:'.$template->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6008,
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
                'data' => 'templates:delete_confirm:'.$template->id,
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('workout_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_user_can_toggle_exercises_in_template_menu(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $user = User::factory()->create([
            'telegram_id' => 880011,
            'email' => null,
        ]);

        $template = WorkoutTemplate::factory()->forUser($user)->create([
            'name' => 'Menu template',
            'description' => null,
            'is_active' => true,
        ]);

        $exercise = Exercise::query()->where('is_active', true)->firstOrFail();

        $telegramId = 880011;

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6101,
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
                'data' => 'templates:edit_exercises:'.$template->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6102,
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
                'data' => 'templates:exercise_toggle:'.$template->id.':'.$exercise->id,
            ],
        ])->assertOk();

        $templateExercise = WorkoutTemplateExercise::query()
            ->where('workout_template_id', $template->id)
            ->where('exercise_id', $exercise->id)
            ->firstOrFail();

        $this->assertDatabaseHas('workout_template_exercises', [
            'id' => $templateExercise->id,
            'workout_template_id' => $template->id,
            'exercise_id' => $exercise->id,
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6103,
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
                'data' => 'templates:exercise_toggle:'.$template->id.':'.$exercise->id,
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('workout_template_exercises', [
            'id' => $templateExercise->id,
        ]);
    }

    public function test_template_view_shows_small_media_marker_for_exercise_with_media(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $user = User::factory()->create([
            'telegram_id' => 880012,
            'email' => null,
        ]);

        $template = WorkoutTemplate::factory()->forUser($user)->create([
            'name' => 'Media template',
            'description' => null,
            'is_active' => true,
        ]);

        $exercise = Exercise::query()->where('is_active', true)->firstOrFail();
        $exercise->update([
            'media_type' => 'animation',
            'media_value' => 'gif-file-123',
        ]);

        WorkoutTemplateExercise::query()->create([
            'workout_template_id' => $template->id,
            'exercise_id' => $exercise->id,
            'position' => 1,
            'target_sets' => 3,
            'target_repetitions_min' => 6,
            'target_repetitions_max' => 10,
            'target_weight' => null,
            'rest_seconds' => 90,
            'notes' => null,
        ]);

        $telegramId = 880012;

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 6201,
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
                'data' => 'templates:view:'.$template->id,
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'editMessageText')
                && str_contains((string) ($request['text'] ?? ''), '🎞');
        });
    }
}
