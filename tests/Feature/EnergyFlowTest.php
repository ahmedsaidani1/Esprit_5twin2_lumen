<?php

namespace Tests\Feature;

use App\Models\Communaute;
use App\Models\Echange;
use App\Models\Facture;
use App\Models\Installation;
use App\Models\Offre;
use App\Models\Production;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnergyFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function setupData(): array
    {
        $vendeur = User::factory()->create();
        $acheteur = User::factory()->create();
        $communaute = Communaute::create(['nom' => 'Test', 'ville' => 'Tunis']);
        $installation = Installation::create([
            'user_id' => $vendeur->id, 'communaute_id' => $communaute->id,
            'nom' => 'Toit', 'type' => 'solaire', 'puissance_kw' => 10,
        ]);
        $offre = Offre::create([
            'installation_id' => $installation->id, 'quantite_kwh' => 100, 'prix_kwh' => 0.25,
            'date_debut' => today(), 'date_fin' => today()->addDays(10), 'statut' => 'disponible',
        ]);

        return compact('vendeur', 'acheteur', 'communaute', 'installation', 'offre');
    }

    private function echangePayload(array $d, array $override = []): array
    {
        return $override + [
            'vendeur_id' => $d['vendeur']->id, 'acheteur_id' => $d['acheteur']->id,
            'communaute_id' => $d['communaute']->id, 'offre_id' => $d['offre']->id,
            'quantite_kwh' => 50, 'prix_unitaire' => 0.25,
            'date_echange' => now()->format('Y-m-d H:i'), 'statut' => 'propose',
        ];
    }

    public function test_all_pages_load(): void
    {
        $d = $this->setupData();
        $p = Production::create(['installation_id' => $d['installation']->id, 'date' => today(), 'energie_produite_kwh' => 40]);

        foreach (['installations', 'productions', 'offres', 'echanges', 'factures'] as $r) {
            $this->get("/$r")->assertOk();
            $this->get("/$r/create")->assertOk();
        }
        $this->get("/installations/{$d['installation']->id}")->assertOk()->assertSee('Toit');
        $this->get("/installations/{$d['installation']->id}/edit")->assertOk();
        $this->get("/productions/{$p->id}")->assertOk();
        $this->get("/offres/{$d['offre']->id}")->assertOk();
    }

    public function test_production_validation_and_creation(): void
    {
        $d = $this->setupData();

        $this->post('/productions', ['installation_id' => 999, 'date' => 'x', 'energie_produite_kwh' => -1])
            ->assertSessionHasErrors(['installation_id', 'date', 'energie_produite_kwh']);

        $this->post('/productions', [
            'installation_id' => $d['installation']->id, 'date' => today()->toDateString(), 'energie_produite_kwh' => 42.5,
        ])->assertRedirect('/productions');
        $this->assertDatabaseHas('productions', ['energie_produite_kwh' => 42.5]);
    }

    public function test_offre_dates_validation(): void
    {
        $d = $this->setupData();

        $this->post('/offres', [
            'installation_id' => $d['installation']->id, 'quantite_kwh' => 10, 'prix_kwh' => 0.2,
            'date_debut' => '2026-12-10', 'date_fin' => '2026-12-01', 'statut' => 'disponible',
        ])->assertSessionHasErrors('date_fin');
    }

    public function test_echange_from_offre_reserves_it_and_deleting_frees_it(): void
    {
        $d = $this->setupData();

        $this->post('/echanges', $this->echangePayload($d))->assertRedirect('/echanges');
        $this->assertSame('reservee', $d['offre']->fresh()->statut);

        $echange = Echange::first();
        $this->delete("/echanges/{$echange->id}");
        $this->assertSame('disponible', $d['offre']->fresh()->statut);
    }

    public function test_refused_echange_frees_the_offre(): void
    {
        $d = $this->setupData();
        $this->post('/echanges', $this->echangePayload($d));
        $echange = Echange::first();

        $this->put("/echanges/{$echange->id}", $this->echangePayload($d, ['statut' => 'refuse']));
        $this->assertSame('disponible', $d['offre']->fresh()->statut);
    }

    public function test_reserved_offre_cannot_be_used_twice(): void
    {
        $d = $this->setupData();
        $this->post('/echanges', $this->echangePayload($d));

        $this->post('/echanges', $this->echangePayload($d))->assertSessionHasErrors('offre_id');
        $this->assertSame(1, Echange::count());
    }

    public function test_echange_must_match_offre_owner_and_quantity(): void
    {
        $d = $this->setupData();

        $this->post('/echanges', $this->echangePayload($d, ['vendeur_id' => $d['acheteur']->id, 'acheteur_id' => $d['vendeur']->id]))
            ->assertSessionHasErrors('vendeur_id');
        $this->post('/echanges', $this->echangePayload($d, ['quantite_kwh' => 500]))
            ->assertSessionHasErrors('quantite_kwh');
        $this->assertSame(0, Echange::count());
    }

    public function test_facture_amount_is_computed_from_echange(): void
    {
        $d = $this->setupData();
        $this->post('/echanges', $this->echangePayload($d));
        $echange = Echange::first();

        $this->post('/factures', [
            'echange_id' => $echange->id, 'date_emission' => today()->toDateString(),
            'date_echeance' => today()->addDays(30)->toDateString(), 'statut' => 'impayee',
        ])->assertRedirect('/factures');

        $this->assertEquals(12.5, Facture::first()->montant_total);
    }

    public function test_expired_offres_are_flagged_on_listing(): void
    {
        $d = $this->setupData();
        $d['offre']->update(['date_fin' => today()->subDay()]);

        $this->get('/offres')->assertOk();
        $this->assertSame('expiree', $d['offre']->fresh()->statut);
    }
}
