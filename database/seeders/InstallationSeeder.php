<?php

namespace Database\Seeders;

use App\Models\Communaute;
use App\Models\Installation;
use App\Models\Offre;
use App\Models\Production;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstallationSeeder extends Seeder
{
    public function run(): void
    {
        // Les installations appartiennent à des membres (pas à l'administrateur)
        $userIds = User::role('membre')->orderBy('id')->pluck('id');
        $communauteIds = Communaute::orderBy('id')->pluck('id');

        if ($userIds->isEmpty()) {
            $this->command->warn('Aucun membre : installations de démonstration non créées (voir DemoSeeder).');

            return;
        }

        $installations = collect([
            ['nom' => 'Toit solaire Ariana', 'type' => 'solaire', 'puissance_kw' => 12.5, 'localisation' => 'Ariana'],
            ['nom' => 'Ferme éolienne Bizerte', 'type' => 'eolien', 'puissance_kw' => 250, 'localisation' => 'Bizerte'],
            ['nom' => 'Panneaux Sousse Nord', 'type' => 'solaire', 'puissance_kw' => 30, 'localisation' => 'Sousse'],
        ])->map(fn ($data, $i) => Installation::create($data + [
            'user_id' => $userIds[$i % $userIds->count()],
            'communaute_id' => $communauteIds[$i % $communauteIds->count()] ?? null,
        ]));

        foreach ($installations as $installation) {
            foreach (range(0, 4) as $d) {
                Production::create([
                    'installation_id' => $installation->id,
                    'date' => now()->subDays($d)->toDateString(),
                    'energie_produite_kwh' => round($installation->puissance_kw * rand(2, 5), 2),
                ]);
            }

            Offre::create([
                'installation_id' => $installation->id,
                'quantite_kwh' => round($installation->puissance_kw * 3, 2),
                'prix_kwh' => 0.25,
                'date_debut' => now()->toDateString(),
                'date_fin' => now()->addDays(30)->toDateString(),
                'statut' => 'disponible',
            ]);
        }
    }
}
