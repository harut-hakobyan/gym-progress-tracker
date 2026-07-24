<?php

namespace Tests\Unit;

use App\Models\Exercise;
use App\Models\Workout;
use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Services\Records\PersonalRecordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalRecordServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_from_workout_set_creates_personal_records(): void
    {
        $this->seed();

        $exercise = Exercise::query()->firstOrFail();
        $workout = Workout::factory()->create([
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'duration_seconds' => 3600,
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

        $this->assertDatabaseHas('personal_records', [
            'user_id' => $workout->user_id,
            'exercise_id' => $exercise->id,
            'type' => 'max_weight',
            'value' => '82.50',
        ]);

        $this->assertDatabaseCount('personal_records', 4);
    }
}
