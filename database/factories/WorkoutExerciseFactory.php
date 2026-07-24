<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutExercise>
 */
class WorkoutExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(),
            'exercise_id' => Exercise::factory(),
            'position' => 1,
            'notes' => null,
        ];
    }
}
