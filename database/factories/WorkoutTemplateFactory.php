<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkoutTemplate>
 */
class WorkoutTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'name' => fake()->unique()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }
}
