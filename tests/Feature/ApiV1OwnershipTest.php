<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1OwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_foreign_workout_or_template(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@example.com',
            'password' => Hash::make('password123'),
        ]);
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'password' => Hash::make('password123'),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        $workout = Workout::factory()->for($otherUser)->active()->create();
        $template = WorkoutTemplate::factory()->forUser($otherUser)->create();
        $muscleGroup = MuscleGroup::factory()->create();
        $exercise = Exercise::factory()->create([
            'muscle_group_id' => $muscleGroup->id,
            'user_id' => null,
            'is_custom' => false,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/workouts/'.$workout->id)
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/workouts/'.$workout->id, [
                'name' => 'Hijacked workout',
            ])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/workouts/'.$workout->id)
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/workout-templates/'.$template->id)
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/workout-templates/'.$template->id, [
                'name' => 'Hijacked template',
            ])
            ->assertForbidden();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/workout-templates/'.$template->id.'/exercises', [
                'exercise_id' => $exercise->id,
                'target_sets' => 3,
            ])
            ->assertForbidden();
    }
}
