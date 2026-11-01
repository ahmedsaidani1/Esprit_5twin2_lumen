<?php

namespace Database\Factories;

use App\Models\Echange;
use App\Models\Facture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facture>
 */
class FactureFactory extends Factory
{
    public function definition(): array
    {
        return [
            'echange_id' => Echange::factory()->state(['statut' => 'accepte']),
            'montant_total' => fake()->randomFloat(2, 2, 40),
            'date_emission' => today(),
            'date_echeance' => today()->addDays(30),
            'statut' => 'impayee',
        ];
    }
}
