<?php

namespace Tests\Unit;

use App\Enums\UserGoalType;
use App\Enums\WorkoutStatus;
use App\Models\User;
use App\Models\UserGoal;
use App\Models\Workout;
use App\Services\Goals\GoalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_workout_goal_progress_is_calculated(): void
    {
        $service = app(GoalService::class);
        $user = User::factory()->create(['email' => null]);

        $goal = UserGoal::factory()->create([
            'user_id' => $user->id,
            'type' => UserGoalType::WeeklyWorkouts->value,
            'target_value' => 3,
        ]);

        Workout::factory()->create([
            'user_id' => $user->id,
            'status' => WorkoutStatus::Completed,
            'completed_at' => now()->subDays(2),
        ]);

        Workout::factory()->create([
            'user_id' => $user->id,
            'status' => WorkoutStatus::Completed,
            'completed_at' => now()->subDay(),
        ]);

        $progress = $service->progress($goal);

        $this->assertSame(2.0, (float) $progress['current_value']);
        $this->assertSame(66.7, (float) $progress['progress_percent']);
    }
}
