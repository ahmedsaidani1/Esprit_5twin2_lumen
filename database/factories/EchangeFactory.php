<?php

namespace Database\Factories;

use App\Models\Communaute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Echange>
 */
class EchangeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendeur_id' => User::factory(),
            'acheteur_id' => User::factory(),
            'communaute_id' => Communaute::factory(),
            'offre_id' => null,
            'quantite_kwh' => fake()->randomFloat(2, 5, 100),
            'prix_unitaire' => fake()->randomFloat(4, 0.15, 0.40),
            'date_echange' => fake()->dateTimeBetween('-90 days', 'now'),
            'statut' => 'propose',
        ];
    }
}
