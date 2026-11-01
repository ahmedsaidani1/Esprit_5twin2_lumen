<?php

namespace Database\Factories;

use App\Models\Compteur;
use App\Models\Consommation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consommation>
 */
class ConsommationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'compteur_id' => Compteur::factory(),
            'date' => fake()->unique()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'energie_consommee_kwh' => fake()->randomFloat(2, 5, 30),
        ];
    }
}
