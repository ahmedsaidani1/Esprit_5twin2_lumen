<?php

namespace Tests\Feature;

use App\Models\Communaute;
use App\Models\Echange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommandationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ranks_the_most_compatible_seller_first(): void
    {
        $community = Communaute::create(['nom' => 'Solaire', 'ville' => 'Tunis']);
        $buyer = User::factory()->create(['name' => 'Acheteur test']);
        $bestSeller = User::factory()->create(['name' => 'Vendeur compatible']);
        $otherSeller = User::factory()->create(['name' => 'Vendeur éloigné']);

        foreach ([0.27, 0.29] as $price) {
            Echange::create([
                'vendeur_id' => $bestSeller->id,
                'acheteur_id' => $buyer->id,
                'communaute_id' => $community->id,
                'quantite_kwh' => 10,
                'prix_unitaire' => $price,
                'date_echange' => now(),
                'statut' => 'termine',
            ]);
        }

        Echange::create([
            'vendeur_id' => $otherSeller->id,
            'acheteur_id' => $buyer->id,
            'communaute_id' => $community->id,
            'quantite_kwh' => 25,
            'prix_unitaire' => 0.18,
            'date_echange' => now(),
            'statut' => 'accepte',
        ]);

        $response = $this->post(route('recommandations.recommend'), [
            'role' => 'acheteur',
            'communaute_id' => $community->id,
            'quantite_kwh' => 10,
            'prix_unitaire' => 0.28,
        ]);

        $response->assertOk()->assertViewHas('recommendations', function ($recommendations) use ($bestSeller, $otherSeller) {
            return $recommendations->pluck('user_id')->all() === [$bestSeller->id, $otherSeller->id];
        });
    }
}
