<?php

namespace Database\Seeders;

use App\Models\Communaute;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Comptes de démonstration (mot de passe : "password") :
     *   admin@solarshare.test  → administrateur
     *   test@example.com       → membre
     */
    public function run(): void
    {
        $communautes = collect([
            ['nom' => 'Communauté Solaire Tunis', 'ville' => 'Tunis'],
            ['nom' => 'Communauté Éolienne Sfax', 'ville' => 'Sfax'],
            ['nom' => 'Communauté Verte Sousse', 'ville' => 'Sousse'],
        ])->map(fn ($c) => Communaute::create($c));

        User::factory()->admin()->create([
            'name' => 'Admin SolarShare',
            'email' => 'admin@solarshare.test',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'communaute_id' => $communautes[0]->id,
        ]);

        User::factory(4)->create()->each(
            fn ($u, $i) => $u->update(['communaute_id' => $communautes[($i + 1) % $communautes->count()]->id])
        );

        $this->call(InstallationSeeder::class);
    }
}
