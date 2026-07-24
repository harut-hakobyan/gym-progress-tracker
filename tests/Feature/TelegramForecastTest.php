<?php

namespace Tests\Feature;

use App\Enums\WorkoutStatus;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramForecastTest extends TestCase
{
    use RefreshDatabase;

    public function test_exercise_progress_callback_shows_forecast(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->create([
            'telegram_id' => 991101,
            'email' => null,
        ]);

        $exercise = Exercise::factory()->create([
            'name' => 'Bench Press',
            'slug' => 'bench-press',
            'muscle_group_id' => MuscleGroup::factory(),
            'user_id' => $user->id,
            'is_custom' => true,
        ]);

        $this->createWorkoutSample($user, $exercise, 80, 8, now()->subDays(30));
        $this->createWorkoutSample($user, $exercise, 82.5, 8, now()->subDays(20));
        $this->createWorkoutSample($user, $exercise, 85, 8, now()->subDays(10));
        $this->createWorkoutSample($user, $exercise, 87.5, 8, now()->subDay());

        $telegramId = 991101;

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5001,
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
                'text' => '/workout',
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
                    'message_id' => 1,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'workout:template:empty',
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
                    'message_id' => 1,
                    'chat' => [
                        'id' => $telegramId,
                        'type' => 'private',
                    ],
                ],
                'data' => 'workout:exercise:'.$exercise->id,
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 5004,
            'callback_query' => [
                'id' => 'cb-3',
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
                'data' => 'exercise:progress:'.$exercise->id,
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'editMessageText')
                && str_contains((string) $request['text'], 'Прогноз');
        });
    }

    private function createWorkoutSample(User $user, Exercise $exercise, float $weight, int $repetitions, \DateTimeInterface $completedAt): void
    {
        $workout = Workout::query()->create([
            'user_id' => $user->id,
            'workout_template_id' => null,
            'name' => 'Forecast workout',
            'status' => WorkoutStatus::Completed,
            'started_at' => $completedAt,
            'completed_at' => $completedAt,
            'duration_seconds' => 3600,
            'user_body_weight' => null,
            'notes' => null,
        ]);

        $workoutExercise = WorkoutExercise::query()->create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
            'position' => 1,
            'notes' => null,
        ]);

        WorkoutSet::query()->create([
            'workout_exercise_id' => $workoutExercise->id,
            'set_number' => 1,
            'type' => 'working',
            'weight' => $weight,
            'repetitions' => $repetitions,
            'duration_seconds' => null,
            'distance_meters' => null,
            'rpe' => 8,
            'rir' => null,
            'rest_seconds' => 90,
            'is_completed' => true,
            'completed_at' => $completedAt,
            'notes' => null,
        ]);
    }
}
