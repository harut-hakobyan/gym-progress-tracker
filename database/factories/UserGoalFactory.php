<?php

namespace Database\Factories;

use App\Enums\UserGoalStatus;
use App\Enums\UserGoalType;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserGoal>
 */
class UserGoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'exercise_id' => null,
            'type' => UserGoalType::TargetWeight->value,
            'target_value' => 100,
            'target_date' => null,
            'status' => UserGoalStatus::Active->value,
        ];
    }

    public function forExercise(Exercise $exercise): static
    {
        return $this->state(fn (): array => [
            'exercise_id' => $exercise->id,
        ]);
    }
}
