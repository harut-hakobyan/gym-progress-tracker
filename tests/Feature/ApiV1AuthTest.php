<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiV1AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_fetch_me_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonStructure([
                'token_type',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();
    }
}
