<?php

namespace Tests\Feature;

use App\Enums\TelegramState;
use App\Models\User;
use App\Services\Telegram\TelegramStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_command_clears_active_state(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $user = User::factory()->create([
            'telegram_id' => 881001,
            'telegram_username' => 'state_user',
            'email' => null,
        ]);

        app(TelegramStateService::class)->put($user, TelegramState::AwaitingSetWeight, [
            'workout_exercise_id' => 15,
        ]);

        $this->postJson('/api/telegram/webhook/test-secret', [
            'update_id' => 9201,
            'message' => [
                'message_id' => 1,
                'from' => [
                    'id' => 881001,
                    'first_name' => 'State',
                    'username' => 'state_user',
                ],
                'chat' => [
                    'id' => 881001,
                    'type' => 'private',
                ],
                'date' => time(),
                'text' => '/cancel',
            ],
        ])->assertOk();

        $this->assertDatabaseMissing('user_telegram_states', [
            'user_id' => $user->id,
        ]);
    }
}
