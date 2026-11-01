<?php

namespace Tests\Feature;

use App\Models\Compteur;
use App\Models\Consommation;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Membre C : CRUD des compteurs et des relevés, avec leurs règles métier et leurs permissions. */
class CompteursConsommationsTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;

    private User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alice = User::factory()->create();
        $this->bob = User::factory()->create();
    }

    private function compteurPayload(array $override = []): array
    {
        return $override + [
            'reference' => 'CPT-TEST-1', 'date_installation' => today()->subYear()->toDateString(), 'statut' => 'actif',
        ];
    }

    // ---------- Compteurs ----------

    public function test_member_crud_on_their_own_meter(): void
    {
        $this->actingAs($this->alice);
        $this->get('/compteurs/create')->assertOk();

        $this->post('/compteurs', $this->compteurPayload(['user_id' => $this->bob->id])) // usurpation ignorée
            ->assertRedirect();
        $compteur = Compteur::where('reference', 'CPT-TEST-1')->firstOrFail();
        $this->assertSame($this->alice->id, $compteur->user_id);

        $this->get('/compteurs')->assertOk()->assertSee('CPT-TEST-1');
        $this->get("/compteurs/{$compteur->id}")->assertOk();
        $this->get("/compteurs/{$compteur->id}/edit")->assertOk();
        $this->put("/compteurs/{$compteur->id}", $this->compteurPayload(['statut' => 'inactif']))->assertRedirect('/compteurs');
        $this->assertSame('inactif', $compteur->fresh()->statut);

        $this->delete("/compteurs/{$compteur->id}")->assertRedirect('/compteurs');
        $this->assertModelMissing($compteur);
    }

    public function test_meter_validation(): void
    {
        Compteur::factory()->create(['reference' => 'CPT-PRIS', 'user_id' => $this->bob->id]);

        $this->actingAs($this->alice)->post('/compteurs', [
            'reference' => 'CPT-PRIS', 'date_installation' => today()->addDay()->toDateString(), 'statut' => 'cassé',
        ])->assertSessionHasErrors(['reference', 'date_installation', 'statut']);

        $this->post('/compteurs', $this->compteurPayload(['reference' => str_repeat('X', 51)]))
            ->assertSessionHasErrors('reference');
    }

    public function test_linked_installation_must_belong_to_the_owner(): void
    {
        $toitBob = Installation::factory()->create(['user_id' => $this->bob->id]);

        $this->actingAs($this->alice)->post('/compteurs', $this->compteurPayload(['installation_id' => $toitBob->id]))
            ->assertSessionHasErrors('installation_id');

        $toitAlice = Installation::factory()->create(['user_id' => $this->alice->id]);
        $this->post('/compteurs', $this->compteurPayload(['installation_id' => $toitAlice->id]))->assertSessionHasNoErrors();
    }

    public function test_meters_are_private(): void
    {
        $compteur = Compteur::factory()->create(['user_id' => $this->bob->id, 'reference' => 'CPT-BOB']);

        $this->actingAs($this->alice);
        $this->get('/compteurs')->assertOk()->assertDontSee('CPT-BOB');
        $this->get("/compteurs/{$compteur->id}")->assertForbidden();
        $this->put("/compteurs/{$compteur->id}", $this->compteurPayload())->assertForbidden();
        $this->delete("/compteurs/{$compteur->id}")->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->get('/compteurs')->assertSee('CPT-BOB');
    }

    public function test_meter_with_readings_cannot_be_deleted(): void
    {
        $compteur = Compteur::factory()->create(['user_id' => $this->alice->id]);
        Consommation::factory()->create(['compteur_id' => $compteur->id]);

        $this->actingAs($this->alice)->delete("/compteurs/{$compteur->id}")->assertSessionHasErrors('compteur');
        $this->assertModelExists($compteur);
    }

    // ---------- Relevés ----------

    public function test_member_crud_on_readings(): void
    {
        $compteur = Compteur::factory()->create(['user_id' => $this->alice->id]);
        $this->actingAs($this->alice);

        $this->get('/consommations/create')->assertOk()->assertSee($compteur->reference);
        $this->post('/consommations', [
            'compteur_id' => $compteur->id, 'date' => today()->toDateString(), 'energie_consommee_kwh' => 12.5,
        ])->assertRedirect('/consommations');

        $releve = Consommation::firstOrFail();
        $this->get('/consommations')->assertOk()->assertSee('12.50');
        $this->get("/consommations/{$releve->id}")->assertOk();
        $this->put("/consommations/{$releve->id}", [
            'compteur_id' => $compteur->id, 'date' => today()->toDateString(), 'energie_consommee_kwh' => 13,
        ])->assertRedirect('/consommations');
        $this->assertEquals(13, $releve->fresh()->energie_consommee_kwh);

        $this->delete("/consommations/{$releve->id}")->assertRedirect('/consommations');
        $this->assertModelMissing($releve);
    }

    public function test_reading_rules(): void
    {
        $compteur = Compteur::factory()->create(['user_id' => $this->alice->id]);
        $this->actingAs($this->alice);
        $jour = today()->toDateString();

        // valeur négative et date future
        $this->post('/consommations', [
            'compteur_id' => $compteur->id, 'date' => today()->addDay()->toDateString(), 'energie_consommee_kwh' => -1,
        ])->assertSessionHasErrors(['date', 'energie_consommee_kwh']);

        // un seul relevé par compteur et par jour
        $this->post('/consommations', ['compteur_id' => $compteur->id, 'date' => $jour, 'energie_consommee_kwh' => 5]);
        $this->post('/consommations', ['compteur_id' => $compteur->id, 'date' => $jour, 'energie_consommee_kwh' => 6])
            ->assertSessionHasErrors('date');
        $this->assertSame(1, Consommation::count());

        // un compteur en panne ne reçoit pas de nouveau relevé
        $panne = Compteur::factory()->enPanne()->create(['user_id' => $this->alice->id]);
        $this->post('/consommations', ['compteur_id' => $panne->id, 'date' => $jour, 'energie_consommee_kwh' => 5])
            ->assertSessionHasErrors('compteur_id');
    }

    public function test_readings_only_on_own_meters_and_private(): void
    {
        $compteurBob = Compteur::factory()->create(['user_id' => $this->bob->id]);
        $releveBob = Consommation::factory()->create(['compteur_id' => $compteurBob->id, 'energie_consommee_kwh' => 99.99]);

        $this->actingAs($this->alice);
        $this->post('/consommations', [
            'compteur_id' => $compteurBob->id, 'date' => today()->toDateString(), 'energie_consommee_kwh' => 1,
        ])->assertSessionHasErrors('compteur_id');

        $this->get('/consommations')->assertOk()->assertDontSee('99.99');
        $this->get("/consommations/{$releveBob->id}")->assertForbidden();
        $this->delete("/consommations/{$releveBob->id}")->assertForbidden();
    }

    // ---------- API ----------

    public function test_api_crud_and_rules(): void
    {
        $token = ['Authorization' => 'Bearer '.$this->alice->createToken('t')->plainTextToken];

        $id = $this->postJson('/api/compteurs', $this->compteurPayload(), $token)
            ->assertCreated()->assertJsonPath('user_id', $this->alice->id)->json('id');

        $this->postJson('/api/consommations', ['compteur_id' => $id, 'date' => today()->toDateString(), 'energie_consommee_kwh' => 8], $token)
            ->assertCreated();
        $this->postJson('/api/consommations', ['compteur_id' => $id, 'date' => today()->toDateString(), 'energie_consommee_kwh' => 9], $token)
            ->assertStatus(422)->assertJsonValidationErrors('date');

        $this->getJson('/api/consommations', $token)->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/compteurs/$id", [], $token)->assertStatus(422); // a des relevés

        $this->app['auth']->forgetGuards();
        $bob = ['Authorization' => 'Bearer '.$this->bob->createToken('t')->plainTextToken];
        $this->getJson("/api/compteurs/$id", $bob)->assertForbidden();
        $this->getJson('/api/consommations', $bob)->assertOk()->assertJsonCount(0, 'data');
    }
}
