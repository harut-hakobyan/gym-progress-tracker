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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1WorkoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_start_complete_and_set_workout(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);
        $token = $user->createToken('api')->plainTextToken;

        $muscleGroup = MuscleGroup::factory()->create();
        $exercise = Exercise::factory()->create([
            'muscle_group_id' => $muscleGroup->id,
            'user_id' => null,
            'is_custom' => false,
        ]);

        $workoutResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/workouts', [
                'name' => 'Morning session',
            ]);

        $workoutResponse->assertCreated()
            ->assertJsonPath('data.name', 'Morning session');

        $workoutId = $workoutResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/workouts/'.$workoutId.'/start')
            ->assertOk()
            ->assertJsonPath('data.status', WorkoutStatus::Active->value);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/workouts/'.$workoutId.'/exercises', [
                'exercise_id' => $exercise->id,
            ])
            ->assertOk();

        $workoutExerciseId = WorkoutExercise::query()->where('workout_id', $workoutId)->value('id');

        $setResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/workout-exercises/'.$workoutExerciseId.'/sets', [
                'weight' => 80,
                'repetitions' => 8,
                'rpe' => 8,
            ]);

        $setResponse->assertCreated()
            ->assertJsonPath('data.weight', '80.00');

        $workoutSetId = $setResponse->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/workout-sets/'.$workoutSetId, [
                'weight' => 82.5,
                'repetitions' => 8,
            ])
            ->assertOk()
            ->assertJsonPath('data.weight', '82.50');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/workouts/'.$workoutId.'/complete')
            ->assertOk()
            ->assertJsonPath('data.status', WorkoutStatus::Completed->value);

        $this->assertDatabaseHas('workouts', [
            'id' => $workoutId,
            'status' => WorkoutStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('workout_sets', [
            'id' => $workoutSetId,
            'weight' => '82.50',
        ]);
    }
}
