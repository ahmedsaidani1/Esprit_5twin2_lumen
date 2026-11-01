<?php

namespace Database\Factories;

use App\Models\Installation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Installation>
 */
class InstallationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'communaute_id' => null,
            'nom' => 'Toit '.fake()->unique()->lastName(),
            'type' => fake()->randomElement(Installation::TYPES),
            'puissance_kw' => fake()->randomFloat(2, 3, 50),
            'localisation' => fake()->city(),
        ];
    }

    public function solaire(): static
    {
        return $this->state(['type' => 'solaire']);
    }
}
