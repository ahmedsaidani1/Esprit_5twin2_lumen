<?php

namespace Database\Factories;

use App\Models\Installation;
use App\Models\Offre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offre>
 */
class OffreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'installation_id' => Installation::factory(),
            'quantite_kwh' => fake()->randomFloat(2, 10, 150),
            'prix_kwh' => fake()->randomFloat(4, 0.15, 0.40),
            'date_debut' => today(),
            'date_fin' => today()->addDays(fake()->numberBetween(7, 30)),
            'statut' => 'disponible',
        ];
    }
}
