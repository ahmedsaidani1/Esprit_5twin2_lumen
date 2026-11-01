<?php

namespace Database\Factories;

use App\Models\Compteur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Compteur>
 */
class CompteurFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'installation_id' => null,
            'reference' => 'CPT-'.fake()->unique()->numerify('#####'),
            'date_installation' => fake()->dateTimeBetween('-2 years', '-100 days')->format('Y-m-d'),
            'statut' => 'actif',
        ];
    }

    public function enPanne(): static
    {
        return $this->state(['statut' => 'en_panne']);
    }
}
