<?php

namespace Tests\Feature;

use App\Models\Communaute;
use App\Models\Echange;
use App\Models\Facture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacturePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_downloads_an_invoice_as_a_pdf(): void
    {
        $community = Communaute::create(['nom' => 'Solaire', 'ville' => 'Tunis']);
        $seller = User::factory()->create(['name' => 'Vendeur solaire']);
        $buyer = User::factory()->create(['name' => 'Acheteur énergie']);

        $exchange = Echange::create([
            'vendeur_id' => $seller->id,
            'acheteur_id' => $buyer->id,
            'communaute_id' => $community->id,
            'quantite_kwh' => 12.5,
            'prix_unitaire' => 0.25,
            'date_echange' => now(),
            'statut' => 'termine',
        ]);

        $invoice = Facture::create([
            'echange_id' => $exchange->id,
            'montant_total' => 3.13,
            'date_emission' => now()->toDateString(),
            'date_echeance' => now()->addDays(30)->toDateString(),
            'statut' => 'impayee',
        ]);

        $response = $this->get(route('factures.pdf', $invoice));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF', false);
    }
}
