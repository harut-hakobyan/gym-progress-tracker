<?php

namespace Database\Factories;

use App\Enums\WorkoutSetType;
use App\Models\WorkoutExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutSet>
 */
class WorkoutSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_exercise_id' => WorkoutExercise::factory(),
            'set_number' => 1,
            'type' => WorkoutSetType::Working,
            'weight' => fake()->randomFloat(1, 0, 200),
            'repetitions' => fake()->numberBetween(1, 20),
            'duration_seconds' => null,
            'distance_meters' => null,
            'rpe' => fake()->numberBetween(6, 10),
            'rir' => null,
            'rest_seconds' => 90,
            'is_completed' => true,
            'completed_at' => now(),
            'notes' => null,
        ];
    }
}
