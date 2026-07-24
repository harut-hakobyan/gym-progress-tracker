<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'telegram_id' => fake()->unique()->numberBetween(1000000, 999999999),
            'telegram_username' => fake()->optional()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'preferred_language' => 'ru',
            'timezone' => 'Asia/Yerevan',
            'weight_unit' => 'kg',
        ];
    }

    public function withoutTelegram(): static
    {
        return $this->state(fn (): array => [
            'telegram_id' => null,
            'telegram_username' => null,
        ]);
    }
}
