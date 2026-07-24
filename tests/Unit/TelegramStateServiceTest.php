<?php

namespace Tests\Unit;

use App\Enums\TelegramState;
use App\Models\User;
use App\Services\Telegram\TelegramStateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramStateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_state_is_stored_and_cleared(): void
    {
        $service = app(TelegramStateService::class);
        $user = User::factory()->create([
            'telegram_id' => 999001,
            'email' => null,
        ]);

        $service->put($user, TelegramState::AwaitingSetWeight, ['workout_id' => 123]);

        $state = $service->get($user);

        $this->assertNotNull($state);
        $this->assertSame(TelegramState::AwaitingSetWeight->value, $state->state);
        $this->assertSame(['workout_id' => 123], $state->payload);

        $service->forget($user);

        $this->assertNull($service->get($user));
    }

    public function test_expired_state_is_removed_on_read(): void
    {
        $service = app(TelegramStateService::class);
        $user = User::factory()->create([
            'telegram_id' => 999002,
            'email' => null,
        ]);

        $service->put(
            $user,
            TelegramState::AwaitingGoalValue,
            ['goal_type' => 'weekly_workouts'],
            Carbon::now()->subMinute()
        );

        $this->assertNull($service->get($user));
        $this->assertDatabaseMissing('user_telegram_states', [
            'user_id' => $user->id,
        ]);
    }
}
