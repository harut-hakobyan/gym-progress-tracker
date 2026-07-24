<?php

namespace Database\Factories;

use App\Models\MuscleGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'user_id' => null,
            'muscle_group_id' => MuscleGroup::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'is_custom' => false,
            'is_active' => true,
        ];
    }

    public function customFor(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'is_custom' => true,
        ]);
    }
}
