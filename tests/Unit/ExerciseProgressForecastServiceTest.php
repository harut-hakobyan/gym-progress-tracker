<?php

namespace Tests\Unit;

use App\Enums\WorkoutStatus;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Services\Forecasting\ExerciseProgressForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseProgressForecastServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_forecast_is_null_when_there_are_not_enough_workouts(): void
    {
        $service = app(ExerciseProgressForecastService::class);
        $user = User::factory()->create(['email' => null]);
        $exercise = Exercise::factory()->create([
            'muscle_group_id' => MuscleGroup::factory(),
        ]);

        $this->createWorkoutSample($user, $exercise, 80, 8, now()->subDays(14));
        $this->createWorkoutSample($user, $exercise, 82.5, 8, now()->subDays(7));

        $this->assertNull($service->forecast($user, $exercise));
    }

    public function test_forecast_uses_recent_progress_trend(): void
    {
        $service = app(ExerciseProgressForecastService::class);
        $user = User::factory()->create(['email' => null]);
        $exercise = Exercise::factory()->create([
            'muscle_group_id' => MuscleGroup::factory(),
            'name' => 'Bench Press',
        ]);

        $this->createWorkoutSample($user, $exercise, 80, 8, now()->subDays(30));
        $this->createWorkoutSample($user, $exercise, 82.5, 8, now()->subDays(20));
        $this->createWorkoutSample($user, $exercise, 85, 8, now()->subDays(10));
        $this->createWorkoutSample($user, $exercise, 87.5, 8, now()->subDay());

        $forecast = $service->forecast($user, $exercise, 100);

        $this->assertNotNull($forecast);
        $this->assertSame(87.5, (float) $forecast['current_weight']);
        $this->assertArrayHasKey(30, $forecast['forecasts']);
        $this->assertGreaterThan(87.5, (float) $forecast['forecasts'][30]['weight']);
        $this->assertGreaterThan(0.0, (float) $forecast['confidence']);
        $this->assertNotNull($forecast['target_date']);
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
