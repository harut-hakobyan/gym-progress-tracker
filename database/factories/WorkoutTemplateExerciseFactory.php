<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\WorkoutTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutTemplateExercise>
 */
class WorkoutTemplateExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_template_id' => WorkoutTemplate::factory(),
            'exercise_id' => Exercise::factory(),
            'position' => 1,
            'target_sets' => 3,
            'target_repetitions_min' => 6,
            'target_repetitions_max' => 10,
            'target_weight' => null,
            'rest_seconds' => 90,
            'notes' => null,
        ];
    }
}
