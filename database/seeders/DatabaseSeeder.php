<?php

namespace Database\Seeders;

use App\Models\Communaute;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory(5)->create();

        foreach ([
            ['nom' => 'Communauté Solaire Tunis', 'ville' => 'Tunis'],
            ['nom' => 'Communauté Éolienne Sfax', 'ville' => 'Sfax'],
            ['nom' => 'Communauté Verte Sousse', 'ville' => 'Sousse'],
        ] as $communaute) {
            Communaute::create($communaute);
        }

        $this->call(InstallationSeeder::class);
    }
}
