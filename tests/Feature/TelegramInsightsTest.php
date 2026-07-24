<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Services\Records\PersonalRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_stats_and_records_are_rendered(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $this->seed();

        $user = User::factory()->create([
            'telegram_id' => 991001,
            'email' => null,
        ]);

        $exercise = Exercise::query()->firstOrFail();
        $workout = Workout::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'duration_seconds' => 3600,
            'name' => 'Test workout',
        ]);
        $workoutExercise = WorkoutExercise::factory()->create([
            'workout_id' => $workout->id,
            'exercise_id' => $exercise->id,
        ]);
        $set = WorkoutSet::factory()->create([
            'workout_exercise_id' => $workoutExercise->id,
            'weight' => 82.5,
            'repetitions' => 8,
            'rpe' => 8,
            'completed_at' => now(),
        ]);

        app(PersonalRecordService::class)->syncFromWorkoutSet($set);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4001,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 991001,
                    'first_name' => 'Harut',
                ],
                'chat' => [
                    'id' => 991001,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/history',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4002,
            'message' => [
                'message_id' => 2,
                'from' => [
                    'id' => 991001,
                    'first_name' => 'Harut',
                ],
                'chat' => [
                    'id' => 991001,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/stats',
            ],
        ])->assertOk();

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 4003,
            'message' => [
                'message_id' => 3,
                'from' => [
                    'id' => 991001,
                    'first_name' => 'Harut',
                ],
                'chat' => [
                    'id' => 991001,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/records',
            ],
        ])->assertOk();

        Http::assertSentCount(3);

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'История тренировок');
        });

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Статистика');
        });

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], 'Личные рекорды');
        });
    }
}
