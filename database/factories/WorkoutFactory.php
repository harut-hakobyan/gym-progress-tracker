<?php

namespace Database\Factories;

use App\Enums\WorkoutStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workout>
 */
class WorkoutFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-30 days', 'now');
        $completedAt = (clone $startedAt)->modify('+'.fake()->numberBetween(20, 120).' minutes');

        return [
            'user_id' => User::factory(),
            'workout_template_id' => null,
            'name' => fake()->words(3, true),
            'status' => WorkoutStatus::Completed,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'duration_seconds' => max(1, Carbon::parse($completedAt)->diffInSeconds(Carbon::parse($startedAt))),
            'user_body_weight' => fake()->randomFloat(1, 60, 130),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => WorkoutStatus::Active,
            'started_at' => now(),
            'completed_at' => null,
            'duration_seconds' => null,
        ]);
    }
}
