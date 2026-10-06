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

class CompleteFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(float $produit = 200): array
    {
        $vendeur = User::factory()->create();
        $acheteur = User::factory()->create();
        $communaute = Communaute::create(['nom' => 'Test', 'ville' => 'Tunis']);
        $installation = Installation::create([
            'user_id' => $vendeur->id, 'communaute_id' => $communaute->id,
            'nom' => 'Toit', 'type' => 'solaire', 'puissance_kw' => 10,
        ]);
        Production::create(['installation_id' => $installation->id, 'date' => today(), 'energie_produite_kwh' => $produit]);
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
            'quantite_kwh' => 40, 'prix_unitaire' => 0.5,
            'date_echange' => now()->format('Y-m-d H:i'), 'statut' => 'propose',
        ];
    }

    // ---------- Communautés ----------

    public function test_communaute_crud_pages(): void
    {
        $this->post('/communautes', ['nom' => 'Verte', 'ville' => 'Sfax'])->assertRedirect('/communautes');
        $c = Communaute::where('nom', 'Verte')->firstOrFail();

        $this->get('/communautes')->assertOk()->assertSee('Verte');
        $this->get("/communautes/{$c->id}")->assertOk();
        $this->get("/communautes/{$c->id}/edit")->assertOk();
        $this->put("/communautes/{$c->id}", ['nom' => 'Verte 2'])->assertRedirect('/communautes');
        $this->assertDatabaseHas('communautes', ['nom' => 'Verte 2']);
        $this->delete("/communautes/{$c->id}")->assertRedirect('/communautes');
        $this->assertDatabaseMissing('communautes', ['id' => $c->id]);
    }

    public function test_communaute_with_echanges_cannot_be_deleted(): void
    {
        $d = $this->setupData();
        $this->post('/echanges', $this->echangePayload($d));

        $this->delete("/communautes/{$d['communaute']->id}")->assertSessionHasErrors('communaute');
        $this->assertDatabaseHas('communautes', ['id' => $d['communaute']->id]);
    }

    public function test_communaute_requires_nom(): void
    {
        $this->post('/communautes', ['ville' => 'x'])->assertSessionHasErrors('nom');
    }

    // ---------- Validation rendue en HTML (sans JavaScript) ----------

    public function test_field_errors_are_rendered_in_html_next_to_the_field(): void
    {
        $d = $this->setupData();

        $html = $this->from('/offres/create')->followingRedirects()
            ->post('/offres', [
                'installation_id' => $d['installation']->id, 'quantite_kwh' => 10, 'prix_kwh' => 0.2,
                'date_debut' => '2026-12-10', 'date_fin' => '2026-12-01', 'statut' => 'disponible',
            ])->assertOk()->getContent();

        $this->assertStringContainsString('is-invalid', $html);
        $this->assertMatchesRegularExpression('/name="date_fin"[^>]*is-invalid[^>]*>\s*<div class="invalid-feedback">/s', $html);
        // les valeurs saisies sont conservées
        $this->assertStringContainsString('value="2026-12-01"', $html);
    }

    public function test_forms_carry_html5_constraints_and_no_validation_script(): void
    {
        $this->setupData();

        $html = $this->get('/offres/create')->assertOk()->getContent();
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('min="0.01"', $html);
        $this->assertStringNotContainsString('<script', $html);

        $this->get('/communautes/create')->assertOk()->assertSee('maxlength="255"', false);
    }

    // ---------- Dashboard ----------

    public function test_dashboard_shows_totals(): void
    {
        $this->setupData(200);

        $this->get('/')->assertOk()->assertSee('Tableau de bord')->assertSee('200.00');
    }

    // ---------- Filtres ----------

    public function test_filters_narrow_the_lists(): void
    {
        $d = $this->setupData();
        $this->post('/echanges', $this->echangePayload($d, ['statut' => 'accepte']));

        $this->get('/echanges?statut=refuse')->assertOk()->assertDontSee($d['vendeur']->name);
        $this->get('/echanges?statut=accepte')->assertOk()->assertSee($d['vendeur']->name);
        $this->get('/installations?q=zzz')->assertOk()->assertDontSee('Toit');
        $this->get('/installations?q=Toi')->assertOk()->assertSee('Toit');
        $this->get('/offres?statut=expiree')->assertOk()->assertSee('Aucune offre.');
        $this->get('/offres?statut=reservee')->assertOk()->assertDontSee('Aucune offre.');
        $this->get('/productions?du=' . today()->addDay()->toDateString())->assertOk()->assertSee('Aucune production.');
        $this->get('/factures?statut=payee')->assertOk()->assertSee('Aucune facture.');
        $this->get('/factures?statut=impayee')->assertOk()->assertDontSee('Aucune facture.');
    }

    // ---------- Règles métier ----------

    public function test_offre_cannot_exceed_produced_stock(): void
    {
        $d = $this->setupData(200); // 200 produits, 100 déjà engagés par l'offre existante

        $this->post('/offres', [
            'installation_id' => $d['installation']->id, 'quantite_kwh' => 150, 'prix_kwh' => 0.2,
            'date_debut' => today()->toDateString(), 'date_fin' => today()->addDays(5)->toDateString(), 'statut' => 'disponible',
        ])->assertSessionHasErrors('quantite_kwh');

        $this->post('/offres', [
            'installation_id' => $d['installation']->id, 'quantite_kwh' => 100, 'prix_kwh' => 0.2,
            'date_debut' => today()->toDateString(), 'date_fin' => today()->addDays(5)->toDateString(), 'statut' => 'disponible',
        ])->assertRedirect('/offres');
        $this->assertSame(2, Offre::count());
    }

    public function test_editing_an_offre_does_not_count_itself_against_stock(): void
    {
        $d = $this->setupData(100); // l'offre existante utilise déjà les 100 kWh

        $this->put("/offres/{$d['offre']->id}", [
            'installation_id' => $d['installation']->id, 'quantite_kwh' => 100, 'prix_kwh' => 0.3,
            'date_debut' => today()->toDateString(), 'date_fin' => today()->addDays(10)->toDateString(), 'statut' => 'disponible',
        ])->assertRedirect('/offres');
        $this->assertEquals(0.3, $d['offre']->fresh()->prix_kwh);
    }

    public function test_accepting_an_echange_creates_the_invoice_automatically(): void
    {
        $d = $this->setupData();
        $this->post('/echanges', $this->echangePayload($d, ['statut' => 'propose']));
        $this->assertSame(0, Facture::count());

        $echange = Echange::first();
        $this->put("/echanges/{$echange->id}", $this->echangePayload($d, ['statut' => 'accepte']));

        $facture = Facture::firstOrFail();
        $this->assertSame($echange->id, $facture->echange_id);
        $this->assertEquals(20.0, $facture->montant_total); // 40 kWh × 0.5
        $this->assertSame('impayee', $facture->statut);

        // Une seconde sauvegarde ne crée pas de doublon
        $this->put("/echanges/{$echange->id}", $this->echangePayload($d, ['statut' => 'termine']));
        $this->assertSame(1, Facture::count());
    }

    public function test_refusing_an_echange_cancels_its_unpaid_invoice(): void
    {
        $d = $this->setupData();
        $this->post('/echanges', $this->echangePayload($d, ['statut' => 'accepte']));
        $echange = Echange::first();

        $this->put("/echanges/{$echange->id}", $this->echangePayload($d, ['statut' => 'refuse']));
        $this->assertSame('annulee', Facture::first()->statut);
        $this->assertSame('disponible', $d['offre']->fresh()->statut);
    }

    public function test_paid_invoice_is_not_cancelled_on_refusal(): void
    {
        $d = $this->setupData();
        $this->post('/echanges', $this->echangePayload($d, ['statut' => 'accepte']));
        $echange = Echange::first();
        Facture::first()->update(['statut' => 'payee']);

        $this->put("/echanges/{$echange->id}", $this->echangePayload($d, ['statut' => 'refuse']));
        $this->assertSame('payee', Facture::first()->statut);
    }

    public function test_artisan_command_expires_offres(): void
    {
        $d = $this->setupData();
        $d['offre']->update(['date_fin' => today()->subDay()]);

        $this->artisan('offres:expirer')->assertExitCode(0);
        $this->assertSame('expiree', $d['offre']->fresh()->statut);
    }

    // ---------- API ----------

    public function test_api_lists_all_resources_as_json(): void
    {
        $this->setupData();

        foreach (['communautes', 'installations', 'productions', 'offres', 'echanges', 'factures'] as $r) {
            $this->getJson("/api/$r")->assertOk()->assertJsonStructure(['data', 'current_page', 'total']);
        }
        $this->getJson('/api/users')->assertOk()->assertJsonCount(2);
        $this->getJson('/api/stats')->assertOk()->assertJsonStructure(['compteurs', 'energie', 'factures']);
    }

    public function test_api_validation_returns_422_json(): void
    {
        $this->postJson('/api/communautes', [])->assertStatus(422)->assertJsonValidationErrors('nom');
        // Même sans en-tête Accept : le middleware force le JSON
        $this->post('/api/communautes', [])->assertStatus(422)->assertJsonValidationErrors('nom');
    }

    public function test_api_crud_cycle(): void
    {
        $id = $this->postJson('/api/communautes', ['nom' => 'API', 'ville' => 'Gabes'])
            ->assertCreated()->json('id');

        $this->getJson("/api/communautes/$id")->assertOk()->assertJsonPath('nom', 'API');
        $this->putJson("/api/communautes/$id", ['nom' => 'API v2'])->assertOk()->assertJsonPath('nom', 'API v2');
        $this->getJson('/api/communautes?q=v2')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/communautes/$id")->assertNoContent();
        $this->getJson("/api/communautes/$id")->assertNotFound();
    }

    public function test_api_applies_the_same_business_rules(): void
    {
        $d = $this->setupData(200);

        // stock insuffisant
        $this->postJson('/api/offres', [
            'installation_id' => $d['installation']->id, 'quantite_kwh' => 999, 'prix_kwh' => 0.2,
            'date_debut' => today()->toDateString(), 'date_fin' => today()->addDay()->toDateString(), 'statut' => 'disponible',
        ])->assertStatus(422)->assertJsonValidationErrors('quantite_kwh');

        // échange accepté => offre réservée + facture auto
        $this->postJson('/api/echanges', $this->echangePayload($d, ['statut' => 'accepte']))->assertCreated();
        $this->assertSame('reservee', $d['offre']->fresh()->statut);
        $this->assertSame(1, Facture::count());

        // l'offre n'est plus disponible
        $this->postJson('/api/echanges', $this->echangePayload($d))->assertStatus(422)->assertJsonValidationErrors('offre_id');

        // suppression de l'échange libère l'offre
        $this->deleteJson('/api/echanges/' . Echange::first()->id)->assertNoContent();
        $this->assertSame('disponible', $d['offre']->fresh()->statut);
    }

    public function test_api_communaute_with_echanges_returns_409(): void
    {
        $d = $this->setupData();
        $this->postJson('/api/echanges', $this->echangePayload($d))->assertCreated();

        $this->deleteJson("/api/communautes/{$d['communaute']->id}")->assertStatus(409);
    }
}
