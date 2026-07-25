<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramTemplateFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_template_from_muscle_group_split(): void
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
                'data' => 'templates:split:chest_triceps',
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
                'data' => 'templates:done',
            ],
        ])->assertOk();

        $user = User::query()->where('telegram_id', $telegramId)->firstOrFail();
        $template = WorkoutTemplate::query()
            ->where('user_id', $user->id)
            ->where('name', 'Upper Push')
            ->with('templateExercises')
            ->firstOrFail();

        $chest = MuscleGroup::query()->where('name', 'Грудь')->firstOrFail();
        $triceps = MuscleGroup::query()->where('name', 'Трицепс')->firstOrFail();
        $expectedExercises = Exercise::query()
            ->whereIn('muscle_group_id', [$chest->id, $triceps->id])
            ->pluck('id')
            ->all();

        $this->assertCount(4, $template->templateExercises);

        foreach ($expectedExercises as $exerciseId) {
            $this->assertDatabaseHas('workout_template_exercises', [
                'workout_template_id' => $template->id,
                'exercise_id' => $exerciseId,
            ]);
        }
    }
}
