<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Communaute>
 */
class CommunauteFactory extends Factory
{
    public function definition(): array
    {
        $ville = fake()->randomElement(['Tunis', 'Sfax', 'Sousse', 'Bizerte', 'Gabès', 'Nabeul', 'Monastir']);

        return [
            'nom' => 'Communauté ' . fake()->unique()->word() . ' ' . $ville,
            'ville' => $ville,
        ];
    }
}
