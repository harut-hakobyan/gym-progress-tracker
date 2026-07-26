<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1ExerciseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_create_and_is_blocked_from_foreign_exercise_updates(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);
        $token = $user->createToken('api')->plainTextToken;

        $muscleGroup = MuscleGroup::factory()->create();
        $systemExercise = Exercise::factory()->create([
            'muscle_group_id' => $muscleGroup->id,
            'user_id' => null,
            'is_custom' => false,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/exercises')
            ->assertOk()
            ->assertJsonFragment(['id' => $systemExercise->id]);

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/exercises', [
                'muscle_group_id' => $muscleGroup->id,
                'name' => 'DB Row',
                'is_custom' => true,
                'is_active' => true,
                'translations' => [
                    'en' => [
                        'name' => 'Database Row',
                    ],
                    'hy' => [
                        'name' => 'Տվյալների տող',
                    ],
                ],
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.name', 'DB Row');

        $createdExercise = Exercise::query()->where('name', 'DB Row')->firstOrFail();

        $this->assertDatabaseHas('exercise_translations', [
            'exercise_id' => $createdExercise->id,
            'locale' => 'en',
            'name' => 'Database Row',
        ]);

        $this->assertDatabaseHas('exercise_translations', [
            'exercise_id' => $createdExercise->id,
            'locale' => 'hy',
            'name' => 'Տվյալների տող',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/exercises/'.$createdExercise->id, [
                'translations' => [
                    'en' => [
                        'name' => 'Database Row Updated',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'DB Row');

        $this->assertDatabaseHas('exercise_translations', [
            'exercise_id' => $createdExercise->id,
            'locale' => 'en',
            'name' => 'Database Row Updated',
        ]);

        $otherUser = User::factory()->create();
        $foreignExercise = Exercise::factory()->create([
            'user_id' => $otherUser->id,
            'muscle_group_id' => $muscleGroup->id,
            'is_custom' => true,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/exercises/'.$foreignExercise->id, [
                'name' => 'Hacked',
            ])
            ->assertForbidden();
    }
}
