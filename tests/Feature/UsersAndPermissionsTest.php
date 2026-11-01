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

class UsersAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $alice;   // propriétaire / vendeur

    private User $bob;     // acheteur

    private User $carol;   // étrangère aux échanges

    private Communaute $communaute;

    private Installation $installation;

    private Offre $offre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->alice = User::factory()->create(['name' => 'Alice Vendeuse']);
        $this->bob = User::factory()->create(['name' => 'Bob Acheteur']);
        $this->carol = User::factory()->create(['name' => 'Carol Etrangere']);
        $this->communaute = Communaute::create(['nom' => 'Comm', 'ville' => 'Tunis']);
        $this->installation = Installation::create([
            'user_id' => $this->alice->id, 'communaute_id' => $this->communaute->id,
            'nom' => 'Toit Alice', 'type' => 'solaire', 'puissance_kw' => 10,
        ]);
        Production::create(['installation_id' => $this->installation->id, 'date' => today(), 'energie_produite_kwh' => 500]);
        $this->offre = Offre::create([
            'installation_id' => $this->installation->id, 'quantite_kwh' => 100, 'prix_kwh' => 0.25,
            'date_debut' => today(), 'date_fin' => today()->addDays(10), 'statut' => 'disponible',
        ]);
    }

    private function echangePayload(array $override = []): array
    {
        return $override + [
            'vendeur_id' => $this->alice->id, 'acheteur_id' => $this->bob->id,
            'communaute_id' => $this->communaute->id, 'offre_id' => $this->offre->id,
            'quantite_kwh' => 40, 'prix_unitaire' => 0.25,
            'date_echange' => now()->format('Y-m-d H:i'), 'statut' => 'propose',
        ];
    }

    // ---------- Connexion / inscription ----------

    public function test_guests_cannot_reach_any_page(): void
    {
        foreach (['/', '/communautes', '/echanges', '/factures', '/installations', '/productions', '/offres', '/users', '/profil'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_login_page_and_successful_login(): void
    {
        $this->get('/login')->assertOk()->assertSee('Connexion');

        $this->post('/login', ['email' => $this->alice->email, 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->alice);
    }

    public function test_wrong_password_is_rejected_with_a_field_error(): void
    {
        $this->post('/login', ['email' => $this->alice->email, 'password' => 'nope'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_redirects_back_to_the_requested_page(): void
    {
        $this->get('/offres')->assertRedirect('/login');
        $this->post('/login', ['email' => $this->alice->email, 'password' => 'password'])->assertRedirect('/offres');
    }

    public function test_registration_creates_a_member_and_ignores_a_role_in_the_request(): void
    {
        $this->post('/register', [
            'name' => 'Nouveau', 'email' => 'nouveau@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
            'role' => 'admin', // tentative d'élévation de privilège
        ])->assertRedirect('/');

        $user = User::where('email', 'nouveau@example.com')->firstOrFail();
        $this->assertSame('membre', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_validation(): void
    {
        $this->post('/register', [
            'name' => '', 'email' => $this->alice->email, 'password' => 'short', 'password_confirmation' => 'other',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_logout(): void
    {
        $this->actingAs($this->alice)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_logged_in_users_cannot_open_login_again(): void
    {
        $this->actingAs($this->alice)->get('/login')->assertRedirect();
    }

    // ---------- Profil ----------

    public function test_profile_update_and_password_change(): void
    {
        $this->actingAs($this->alice)->get('/profil')->assertOk();

        $this->from('/profil')->put('/user/profile-information', ['name' => 'Alice 2', 'email' => $this->alice->email])
            ->assertRedirect('/profil')->assertSessionHasNoErrors();
        $this->assertSame('Alice 2', $this->alice->fresh()->name);

        // changer le mot de passe exige l'ancien
        $this->from('/profil')->put('/user/password', [
            'current_password' => 'wrong', 'password' => 'newsecret1', 'password_confirmation' => 'newsecret1',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->from('/profil')->put('/user/password', [
            'current_password' => 'password', 'password' => 'newsecret1', 'password_confirmation' => 'newsecret1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(\Hash::check('newsecret1', $this->alice->fresh()->password));
    }

    public function test_profile_cannot_change_the_role(): void
    {
        $this->actingAs($this->alice)->put('/user/profile-information', [
            'name' => 'Alice', 'email' => $this->alice->email, 'role' => 'admin',
        ]);
        $this->assertSame('membre', $this->alice->fresh()->role);
        $this->assertFalse($this->alice->fresh()->isAdmin());
    }

    // ---------- Gestion des utilisateurs (admin) ----------

    public function test_members_cannot_manage_users(): void
    {
        $this->actingAs($this->alice);

        $this->get('/users')->assertForbidden();
        $this->get('/users/create')->assertForbidden();
        $this->post('/users', ['name' => 'x', 'email' => 'x@x.com', 'role' => 'admin', 'password' => 'password1', 'password_confirmation' => 'password1'])->assertForbidden();
        $this->delete("/users/{$this->bob->id}")->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'x@x.com']);
    }

    public function test_admin_user_crud(): void
    {
        $this->actingAs($this->admin);

        $this->get('/users')->assertOk()->assertSee($this->alice->email);
        $this->post('/users', [
            'name' => 'Dave', 'email' => 'dave@example.com', 'role' => 'membre',
            'communaute_id' => $this->communaute->id, 'password' => 'password1', 'password_confirmation' => 'password1',
        ])->assertRedirect('/users');

        $dave = User::where('email', 'dave@example.com')->firstOrFail();
        $this->get("/users/{$dave->id}")->assertOk();
        $this->get("/users/{$dave->id}/edit")->assertOk();

        $this->put("/users/{$dave->id}", ['name' => 'Dave 2', 'email' => 'dave@example.com', 'role' => 'admin'])
            ->assertRedirect('/users');
        $this->assertSame('admin', $dave->fresh()->role);
        $this->assertTrue(\Hash::check('password1', $dave->fresh()->password)); // mot de passe inchangé si vide

        $this->get('/users?role=membre&q=Alice')->assertOk()->assertSee('Alice Vendeuse');
        $this->delete("/users/{$dave->id}")->assertRedirect('/users');
        $this->assertModelMissing($dave);
    }

    public function test_admin_cannot_delete_or_demote_themselves(): void
    {
        $this->actingAs($this->admin);

        $this->delete("/users/{$this->admin->id}")->assertForbidden();
        $this->put("/users/{$this->admin->id}", ['name' => 'A', 'email' => $this->admin->email, 'role' => 'membre'])
            ->assertSessionHasErrors('role');
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_user_with_exchanges_cannot_be_deleted_but_installations_cascade(): void
    {
        $this->actingAs($this->bob)->post('/echanges', $this->echangePayload());

        $this->actingAs($this->admin);
        $this->delete("/users/{$this->alice->id}")->assertSessionHasErrors('user'); // vendeuse d'un échange
        $this->assertModelExists($this->alice);

        // Carol n'a pas d'échange : son compte et son installation partent ensemble
        $toit = Installation::create(['user_id' => $this->carol->id, 'nom' => 'Toit Carol', 'type' => 'solaire', 'puissance_kw' => 3]);
        $this->delete("/users/{$this->carol->id}")->assertRedirect('/users');
        $this->assertModelMissing($this->carol);
        $this->assertModelMissing($toit);
    }

    // ---------- Installations / productions / offres : propriétaire seulement ----------

    public function test_member_creates_installations_as_owner_even_if_another_owner_is_sent(): void
    {
        $this->actingAs($this->bob)->post('/installations', [
            'user_id' => $this->alice->id, // usurpation ignorée
            'nom' => 'Toit Bob', 'type' => 'solaire', 'puissance_kw' => 5,
        ])->assertRedirect('/installations');

        $this->assertSame($this->bob->id, Installation::where('nom', 'Toit Bob')->value('user_id'));
    }

    public function test_only_the_owner_can_edit_or_delete_an_installation(): void
    {
        $this->actingAs($this->bob);
        $this->get("/installations/{$this->installation->id}/edit")->assertForbidden();
        $this->put("/installations/{$this->installation->id}", ['nom' => 'Piraté', 'type' => 'solaire', 'puissance_kw' => 1])->assertForbidden();
        $this->delete("/installations/{$this->installation->id}")->assertForbidden();
        $this->assertSame('Toit Alice', $this->installation->fresh()->nom);

        $this->actingAs($this->alice);
        $this->put("/installations/{$this->installation->id}", ['nom' => 'Toit Alice 2', 'type' => 'solaire', 'puissance_kw' => 10])
            ->assertRedirect('/installations');
        $this->assertSame('Toit Alice 2', $this->installation->fresh()->nom);
    }

    public function test_members_see_every_installation_but_only_own_buttons(): void
    {
        $this->actingAs($this->bob)->get('/installations')->assertOk()->assertSee('Toit Alice')
            ->assertDontSee("installations/{$this->installation->id}/edit");

        $this->actingAs($this->alice)->get('/installations')->assertOk()
            ->assertSee("installations/{$this->installation->id}/edit");
    }

    public function test_production_and_offre_only_on_own_installations(): void
    {
        $this->actingAs($this->bob);

        $this->post('/productions', [
            'installation_id' => $this->installation->id, 'date' => today()->toDateString(), 'energie_produite_kwh' => 10,
        ])->assertSessionHasErrors('installation_id');

        $this->post('/offres', [
            'installation_id' => $this->installation->id, 'quantite_kwh' => 10, 'prix_kwh' => 0.1,
            'date_debut' => today()->toDateString(), 'date_fin' => today()->addDay()->toDateString(), 'statut' => 'disponible',
        ])->assertSessionHasErrors('installation_id');

        $production = Production::first();
        $this->put("/productions/{$production->id}", ['installation_id' => $this->installation->id, 'date' => today()->toDateString(), 'energie_produite_kwh' => 1])->assertForbidden();
        $this->delete("/offres/{$this->offre->id}")->assertForbidden();

        $this->actingAs($this->alice)->post('/productions', [
            'installation_id' => $this->installation->id, 'date' => today()->toDateString(), 'energie_produite_kwh' => 10,
        ])->assertRedirect('/productions');
    }

    public function test_installation_dropdowns_only_list_own_installations(): void
    {
        $other = Installation::create(['user_id' => $this->bob->id, 'nom' => 'Toit Bob', 'type' => 'eolien', 'puissance_kw' => 3]);

        $html = $this->actingAs($this->bob)->get('/offres/create')->assertOk()->getContent();
        $this->assertStringContainsString('Toit Bob', $html);
        $this->assertStringNotContainsString('Toit Alice', $html);
    }

    // ---------- Communautés ----------

    public function test_only_admin_manages_communautes(): void
    {
        $this->actingAs($this->alice);
        $this->get('/communautes')->assertOk();
        $this->get('/communautes/create')->assertForbidden();
        $this->post('/communautes', ['nom' => 'X'])->assertForbidden();
        $this->put("/communautes/{$this->communaute->id}", ['nom' => 'Y'])->assertForbidden();
        $this->delete("/communautes/{$this->communaute->id}")->assertForbidden();

        $this->actingAs($this->admin)->post('/communautes', ['nom' => 'Nouvelle'])->assertRedirect('/communautes');
    }

    // ---------- Échanges : participants seulement ----------

    public function test_exchange_list_and_pages_are_limited_to_participants(): void
    {
        $this->actingAs($this->bob)->post('/echanges', $this->echangePayload())->assertRedirect('/echanges');
        $echange = Echange::firstOrFail();

        $this->actingAs($this->alice)->get("/echanges/{$echange->id}")->assertOk();
        $this->actingAs($this->bob)->get("/echanges/{$echange->id}")->assertOk();

        $this->actingAs($this->carol);
        $this->get("/echanges/{$echange->id}")->assertForbidden();
        $this->get("/echanges/{$echange->id}/edit")->assertForbidden();
        $this->delete("/echanges/{$echange->id}")->assertForbidden();
        $this->get('/echanges')->assertOk()->assertDontSee('Bob Acheteur');

        $this->actingAs($this->admin)->get('/echanges')->assertOk()->assertSee('Bob Acheteur');
    }

    public function test_member_must_be_a_participant_of_the_exchange_they_create(): void
    {
        $this->actingAs($this->carol)->post('/echanges', $this->echangePayload())->assertSessionHasErrors('acheteur_id');
        $this->assertSame(0, Echange::count());
    }

    public function test_only_the_seller_can_accept_refuse_or_finish_an_exchange(): void
    {
        // l'acheteur ne peut pas créer un échange déjà accepté
        $this->actingAs($this->bob)->post('/echanges', $this->echangePayload(['statut' => 'accepte']))
            ->assertSessionHasErrors('statut');

        $this->post('/echanges', $this->echangePayload());
        $echange = Echange::firstOrFail();

        // ni l'accepter plus tard
        $this->put("/echanges/{$echange->id}", $this->echangePayload(['statut' => 'accepte']))->assertSessionHasErrors('statut');
        $this->assertSame('propose', $echange->fresh()->statut);
        $this->assertSame(0, Facture::count());

        // le vendeur le peut, et la facture est émise automatiquement
        $this->actingAs($this->alice)->put("/echanges/{$echange->id}", $this->echangePayload(['statut' => 'accepte']))
            ->assertRedirect('/echanges');
        $this->assertSame('accepte', $echange->fresh()->statut);
        $this->assertSame(1, Facture::count());
    }

    // ---------- Factures ----------

    public function test_invoices_are_visible_to_both_participants_but_managed_by_the_seller(): void
    {
        $this->actingAs($this->alice)->post('/echanges', $this->echangePayload(['statut' => 'accepte']));
        $facture = Facture::firstOrFail();

        $this->actingAs($this->bob);
        $this->get('/factures')->assertOk()->assertSee('#'.$facture->echange_id);
        $this->get("/factures/{$facture->id}")->assertOk();
        $this->get("/factures/{$facture->id}/edit")->assertForbidden();
        $this->delete("/factures/{$facture->id}")->assertForbidden();

        $this->actingAs($this->carol);
        $this->get("/factures/{$facture->id}")->assertForbidden();
        $this->get('/factures')->assertOk()->assertSee('Aucune facture.');

        $this->actingAs($this->alice)->put("/factures/{$facture->id}", [
            'echange_id' => $facture->echange_id, 'date_emission' => today()->toDateString(),
            'date_echeance' => today()->addDays(10)->toDateString(), 'statut' => 'payee',
        ])->assertRedirect('/factures');
        $this->assertSame('payee', $facture->fresh()->statut);
    }

    public function test_only_the_seller_can_issue_an_invoice_manually(): void
    {
        $this->actingAs($this->bob)->post('/echanges', $this->echangePayload());
        $echange = Echange::firstOrFail();

        $this->post('/factures', [
            'echange_id' => $echange->id, 'date_emission' => today()->toDateString(),
            'date_echeance' => today()->addDays(10)->toDateString(), 'statut' => 'impayee',
        ])->assertSessionHasErrors('echange_id');
    }

    // ---------- Tableau de bord ----------

    public function test_dashboard_hides_other_peoples_exchanges_and_invoices(): void
    {
        $this->actingAs($this->alice)->post('/echanges', $this->echangePayload(['statut' => 'accepte']));

        $alice = $this->actingAs($this->alice)->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Bob Acheteur', $alice);

        $carol = $this->actingAs($this->carol)->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('Bob Acheteur', $carol);
        $this->assertStringContainsString('Aucun échange.', $carol);
    }

    // ---------- API (jetons Sanctum) ----------

    public function test_api_requires_a_token(): void
    {
        $this->getJson('/api/offres')->assertUnauthorized();
        $this->getJson('/api/stats')->assertUnauthorized();
    }

    public function test_api_login_me_and_logout(): void
    {
        $this->postJson('/api/login', ['email' => $this->alice->email, 'password' => 'wrong'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $token = $this->postJson('/api/login', ['email' => $this->alice->email, 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.role', 'membre')->json('token');

        $this->getJson('/api/me', ['Authorization' => "Bearer $token"])->assertOk()->assertJsonPath('email', $this->alice->email);
        $this->getJson('/api/offres', ['Authorization' => "Bearer $token"])->assertOk();

        $this->postJson('/api/logout', [], ['Authorization' => "Bearer $token"])->assertNoContent();
        $this->assertSame(0, $this->alice->tokens()->count());
    }

    public function test_api_applies_the_same_permissions(): void
    {
        // Dans un même test, le guard garde l'utilisateur de la requête précédente : on le réinitialise
        // pour que chaque requête soit authentifiée par son propre jeton, comme en production.
        $token = function (User $u) {
            $this->app['auth']->forgetGuards();

            return ['Authorization' => 'Bearer '.$u->createToken('t')->plainTextToken];
        };

        // un membre ne modifie pas l'installation d'un autre
        $this->putJson("/api/installations/{$this->installation->id}",
            ['nom' => 'x', 'type' => 'solaire', 'puissance_kw' => 1], $token($this->bob))->assertForbidden();

        // un étranger ne voit pas l'échange
        $this->postJson('/api/echanges', $this->echangePayload(), $token($this->bob))->assertCreated();
        $id = Echange::firstOrFail()->id;
        $this->getJson("/api/echanges/$id", $token($this->carol))->assertForbidden();
        $this->getJson('/api/echanges', $token($this->carol))->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/echanges', $token($this->bob))->assertOk()->assertJsonCount(1, 'data');

        // l'acheteur ne peut pas accepter
        $this->putJson("/api/echanges/$id", $this->echangePayload(['statut' => 'accepte']), $token($this->bob))
            ->assertStatus(422)->assertJsonValidationErrors('statut');

        // communautés réservées à l'admin
        $this->postJson('/api/communautes', ['nom' => 'X'], $token($this->bob))->assertForbidden();
        $this->postJson('/api/communautes', ['nom' => 'X'], $token($this->admin))->assertCreated();

        // un membre ne voit les e-mails de personne
        $users = $this->getJson('/api/users', $token($this->bob))->assertOk()->json();
        $this->assertArrayNotHasKey('email', $users[0]);
        $this->assertArrayHasKey('email', $this->getJson('/api/users', $token($this->admin))->json()[0]);
    }

    public function test_api_installation_owner_is_forced_to_the_caller(): void
    {
        $token = ['Authorization' => 'Bearer '.$this->bob->createToken('t')->plainTextToken];

        $this->postJson('/api/installations', ['user_id' => $this->alice->id, 'nom' => 'Bob API', 'type' => 'solaire', 'puissance_kw' => 2], $token)
            ->assertCreated()->assertJsonPath('user_id', $this->bob->id);
    }
}
