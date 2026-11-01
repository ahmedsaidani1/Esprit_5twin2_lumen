<?php

namespace Database\Factories;

use App\Models\Installation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Production>
 */
class ProductionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'installation_id' => Installation::factory(),
            'date' => fake()->unique()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'energie_produite_kwh' => fake()->randomFloat(2, 10, 80),
        ];
    }
}
