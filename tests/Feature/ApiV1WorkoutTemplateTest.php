<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\WorkoutTemplate;
use App\Models\WorkoutTemplateExercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1WorkoutTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_workout_template_exercises_and_copy_templates(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);
        $token = $user->createToken('api')->plainTextToken;

        $muscleGroup = MuscleGroup::factory()->create();
        $exerciseA = Exercise::factory()->create([
            'muscle_group_id' => $muscleGroup->id,
            'user_id' => null,
            'is_custom' => false,
            'name' => 'Bench Press',
        ]);
        $exerciseB = Exercise::factory()->create([
            'muscle_group_id' => $muscleGroup->id,
            'user_id' => null,
            'is_custom' => false,
            'name' => 'Squat',
        ]);

        $template = WorkoutTemplate::factory()->forUser($user)->create([
            'name' => 'Push Day',
        ]);

        $createExercise = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/workout-templates/{$template->id}/exercises", [
                'exercise_id' => $exerciseA->id,
                'target_sets' => 4,
                'target_repetitions_min' => 6,
                'target_repetitions_max' => 8,
                'target_weight' => 80,
                'rest_seconds' => 120,
            ]);

        $createExercise->assertCreated()
            ->assertJsonPath('data.exercise_id', $exerciseA->id);

        $templateExerciseId = $createExercise->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/workout-template-exercises/{$templateExerciseId}", [
                'target_sets' => 5,
                'notes' => 'Focus on control',
            ])
            ->assertOk()
            ->assertJsonPath('data.target_sets', 5);

        $second = WorkoutTemplateExercise::query()->create([
            'workout_template_id' => $template->id,
            'exercise_id' => $exerciseB->id,
            'position' => 2,
            'target_sets' => 3,
            'target_repetitions_min' => 8,
            'target_repetitions_max' => 10,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/workout-templates/{$template->id}/reorder", [
                'exercise_ids' => [$second->id, $templateExerciseId],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $copyResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/workout-templates/{$template->id}/copy", [
                'name' => 'Push Day Copy',
            ]);

        $copyResponse->assertCreated()
            ->assertJsonPath('data.name', 'Push Day Copy');

        $copiedTemplateId = $copyResponse->json('data.id');

        $this->assertDatabaseHas('workout_templates', [
            'id' => $copiedTemplateId,
            'user_id' => $user->id,
            'name' => 'Push Day Copy',
        ]);

        $this->assertDatabaseHas('workout_template_exercises', [
            'workout_template_id' => $copiedTemplateId,
            'exercise_id' => $exerciseA->id,
        ]);
        $this->assertDatabaseHas('workout_template_exercises', [
            'workout_template_id' => $copiedTemplateId,
            'exercise_id' => $exerciseB->id,
        ]);
    }
}
