<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_goals(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);
        $token = $user->createToken('api')->plainTextToken;

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/goals', [
                'type' => 'weekly_workouts',
                'target_value' => 3,
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.type', 'weekly_workouts');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/goals')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }
}
