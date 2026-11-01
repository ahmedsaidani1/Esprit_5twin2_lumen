<?php

namespace Tests\Feature;

use App\Models\Compteur;
use App\Models\Consommation;
use App\Models\Echange;
use App\Models\Facture;
use App\Models\Installation;
use App\Models\Offre;
use App\Models\Production;
use App\Models\User;
use App\Notifications\DemandeAchatRecue;
use App\Notifications\FactureEmise;
use App\Notifications\StatutEchangeModifie;
use App\Services\Ia\AnalyseConsommationService;
use App\Services\Ia\RecommandationOffresService;
use App\Services\Ia\RisqueImpayeService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Fonctionnalités avancées et IA des trois membres, sur les données de démonstration (90 jours). */
class FonctionnalitesAvanceesEtIaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local'); // le modèle entraîné n'est pas écrit sur le vrai disque
        $this->seed(DemoSeeder::class);
    }

    private function user(string $prenom): User
    {
        return User::where('email', "{$prenom}@solarshare.test")->firstOrFail();
    }

    // ====================== Membre A : échanges et factures ======================

    public function test_a_notifications_on_purchase_status_change_and_invoice(): void
    {
        Notification::fake();
        $alice = $this->user('alice');
        $bob = $this->user('bob');
        $offre = Offre::whereHas('installation', fn ($q) => $q->where('user_id', $alice->id))->where('statut', 'disponible')->firstOrFail();
        $payload = [
            'vendeur_id' => $alice->id, 'acheteur_id' => $bob->id, 'communaute_id' => $alice->communaute_id,
            'offre_id' => $offre->id, 'quantite_kwh' => 10, 'prix_unitaire' => $offre->prix_kwh,
            'date_echange' => now()->format('Y-m-d H:i'), 'statut' => 'propose',
        ];

        $this->actingAs($bob)->post('/echanges', $payload)->assertRedirect('/echanges');
        Notification::assertSentTo($alice, DemandeAchatRecue::class);

        $echange = Echange::latest('id')->first();
        $this->actingAs($alice)->put("/echanges/{$echange->id}", ['statut' => 'accepte'] + $payload);
        Notification::assertSentTo($bob, StatutEchangeModifie::class);
        Notification::assertSentTo($bob, FactureEmise::class, fn ($n, $canaux) => in_array('mail', $canaux) && in_array('database', $canaux));
    }

    public function test_a_notification_center(): void
    {
        $bob = $this->user('bob');
        $facture = Facture::firstOrFail();
        $bob->notify(new FactureEmise($facture));

        $this->actingAs($bob)->get('/')->assertSee('badge rounded-pill bg-danger', false);
        $this->get('/notifications')->assertOk()->assertSee('Nouvelle facture');

        $id = $bob->notifications()->first()->id;
        $this->get("/notifications/{$id}")->assertRedirect(route('factures.show', $facture));
        $this->assertSame(0, $bob->unreadNotifications()->count());
    }

    public function test_a_invoice_pdf_and_email_with_attachment(): void
    {
        $facture = Facture::with('echange')->firstOrFail();
        $vendeur = $facture->echange->vendeur;

        $reponse = $this->actingAs($vendeur)->get("/factures/{$facture->id}/pdf")->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $reponse->getContent());

        $mail = (new FactureEmise($facture))->toMail($facture->echange->acheteur);
        $this->assertCount(1, $mail->rawAttachments);
        $this->assertSame(sprintf('facture-%05d.pdf', $facture->id), $mail->rawAttachments[0]['name']);

        Notification::fake();
        $this->post("/factures/{$facture->id}/envoyer")->assertSessionHas('success');
        Notification::assertSentTo($facture->echange->acheteur, FactureEmise::class);

        // un étranger ne peut ni lire le PDF ni renvoyer la facture
        $etranger = User::factory()->create();
        $this->actingAs($etranger)->get("/factures/{$facture->id}/pdf")->assertForbidden();
        $this->post("/factures/{$facture->id}/envoyer")->assertForbidden();
    }

    public function test_a_risk_model_is_trained_evaluated_and_learns_who_pays_late(): void
    {
        $infos = app(RisqueImpayeService::class)->informations();
        $this->assertNotNull($infos);
        $this->assertGreaterThanOrEqual(20, $infos['exemples']['total']);
        $this->assertGreaterThan(0.6, $infos['metriques']['auc']); // mieux que le hasard (0,5)

        // une nouvelle facture : le mauvais payeur (Farah) doit être jugé plus risqué que le bon (Bob)
        // même situation pour les deux : vendeur de leur communauté, même montant, même délai
        $risque = function (User $acheteur, User $vendeur) {
            $e = Echange::create(['vendeur_id' => $vendeur->id, 'acheteur_id' => $acheteur->id, 'communaute_id' => $acheteur->communaute_id,
                'quantite_kwh' => 60, 'prix_unitaire' => 0.27, 'date_echange' => now(), 'statut' => 'accepte']);
            $f = Facture::create(['echange_id' => $e->id, 'montant_total' => 16.2, 'date_emission' => today(),
                'date_echeance' => today()->addDays(15), 'statut' => 'impayee']);

            return app(RisqueImpayeService::class)->predire($f)['probabilite'];
        };
        $bob = $risque($this->user('bob'), $this->user('alice'));
        $farah = $risque($this->user('farah'), $this->user('driss'));
        $this->assertGreaterThan($bob + 0.2, $farah);
        $this->assertSame('élevé', app(RisqueImpayeService::class)->niveau($farah));
        $this->assertSame('faible', app(RisqueImpayeService::class)->niveau($bob));
    }

    public function test_a_risk_is_shown_to_the_seller_but_not_to_the_buyer(): void
    {
        $facture = Facture::where('statut', 'impayee')->with('echange')->firstOrFail();

        $this->actingAs($facture->echange->vendeur)->get("/factures/{$facture->id}")->assertOk()->assertSee('Risque d\'impayé (IA)', false);
        $this->actingAs($facture->echange->vendeur)->get('/factures?statut=impayee')->assertSee('Risque IA')->assertSee(' %');
        $this->actingAs($facture->echange->acheteur)->get("/factures/{$facture->id}")->assertOk()->assertDontSee('Risque d\'impayé (IA)', false);
    }

    public function test_a_offer_recommendations_exclude_own_offers_and_explain_themselves(): void
    {
        $alice = $this->user('alice');
        $this->actingAs($alice)->get('/')->assertOk()->assertSee('Offres recommandées pour vous');

        $reco = app(RecommandationOffresService::class)->pour($alice);
        $this->assertNotEmpty($reco);
        foreach ($reco as $r) {
            $this->assertNotSame($alice->id, $r['offre']->installation->user_id);
            $this->assertNotEmpty($r['raisons']);
        }
        $this->assertSame($reco->pluck('score')->sortDesc()->values()->all(), $reco->pluck('score')->all());
    }

    // ====================== Membre B : production et offres ======================

    public function test_b_installation_page_shows_chart_forecast_and_suggested_offer_to_its_owner(): void
    {
        $alice = $this->user('alice');
        $toit = Installation::where('nom', 'Toit Alice')->firstOrFail();

        $page = $this->actingAs($alice)->get("/installations/{$toit->id}")->assertOk();
        $page->assertSee('graphique-production', false)->assertSee('prévision IA sur 7 jours')
            ->assertSee('Offre suggérée par l\'IA', false)->assertSee('mesurée par le compteur CPT-1001');

        // un autre membre voit la courbe mais pas la suggestion
        $this->actingAs($this->user('bob'))->get("/installations/{$toit->id}")->assertOk()
            ->assertSee('graphique-production', false)->assertDontSee('Offre suggérée par l\'IA', false);
    }

    public function test_b_suggested_offer_prefills_the_offer_form(): void
    {
        $toit = Installation::where('nom', 'Toit Alice')->firstOrFail();
        $this->actingAs($this->user('alice'))
            ->get("/offres/create?installation_id={$toit->id}&quantite_kwh=123.45&prix_kwh=0.2777")
            ->assertOk()->assertSee('value="123.45"', false)->assertSee('value="0.2777"', false);
    }

    public function test_b_csv_import_updates_or_creates_and_reports_bad_lines(): void
    {
        $alice = $this->user('alice');
        $toit = Installation::where('nom', 'Toit Alice')->firstOrFail();
        $hier = today()->subDay()->toDateString();
        $csv = "date;energie_produite_kwh\n{$hier};99,5\n2020-01-01;12\npas-une-date;5\n2020-01-02;-3\n";

        $this->actingAs($alice)->post("/installations/{$toit->id}/productions/import", [
            'fichier' => UploadedFile::fake()->createWithContent('prod.csv', $csv),
        ])->assertRedirect("/installations/{$toit->id}")
            ->assertSessionHas('success', 'Import terminé : 1 production(s) ajoutée(s), 1 mise(s) à jour.')
            ->assertSessionHas('erreurs_import', fn ($e) => count($e) === 2 && str_starts_with($e[0], 'Ligne 4'));

        $this->assertEquals(99.5, $toit->productions()->whereDate('date', $hier)->value('energie_produite_kwh'));

        // pas d'import sur l'installation d'un autre
        $this->actingAs($this->user('bob'))->post("/installations/{$toit->id}/productions/import", [
            'fichier' => UploadedFile::fake()->createWithContent('prod.csv', $csv),
        ])->assertForbidden();
    }

    public function test_b_csv_exports(): void
    {
        $this->actingAs($this->user('alice'));
        $prod = $this->get('/productions/export?installation_id='.Installation::where('nom', 'Toit Alice')->value('id'));
        $prod->assertOk();
        $contenu = $prod->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFinstallation;date;energie_produite_kwh", $contenu);
        $this->assertStringNotContainsString('Ferme solaire Driss', $contenu); // le filtre est respecté

        $this->assertStringContainsString('statut', $this->get('/offres/export')->streamedContent());
    }

    // ====================== Membre C : compteurs et consommation ======================

    public function test_c_anomalies_are_detected_on_the_injected_days_only(): void
    {
        $ia = app(AnalyseConsommationService::class);
        $parReference = Compteur::where('statut', 'actif')->get()->mapWithKeys(fn ($c) => [$c->reference => $ia->anomalies($c)]);

        $this->assertCount(2, $parReference['CPT-1002']);  // Bob : deux pics
        $this->assertSame(['pic', 'pic'], array_column($parReference['CPT-1002'], 'sens'));
        $this->assertSame('chute', $parReference['CPT-1003'][0]['sens']); // Carol : une chute
        $this->assertSame([], $parReference['CPT-1001']);  // Alice : rien
    }

    public function test_c_meter_page_shows_chart_anomalies_and_forecast(): void
    {
        $bob = $this->user('bob');
        $compteur = Compteur::where('reference', 'CPT-1002')->firstOrFail();

        $this->actingAs($bob)->get("/compteurs/{$compteur->id}")->assertOk()
            ->assertSee('graphique-consommation', false)->assertSee('Anomalies détectées par l\'IA', false)
            ->assertSee('⚠ Pic')->assertSee('Prévision sur 30 jours')->assertSee('R²');
    }

    public function test_c_profiles_by_kmeans_and_personal_advice(): void
    {
        $ia = app(AnalyseConsommationService::class);
        $profils = $ia->profils()['profils'];

        $this->assertSame('Intensif', $profils[$this->user('bob')->id]);
        $this->assertSame('Sobre', $profils[$this->user('carol')->id]);

        $this->actingAs($this->user('bob'))->get('/ma-consommation')->assertOk()
            ->assertSee('Intensif')->assertSee('pic(s) de consommation inhabituel(s)')->assertSee('Économie estimée');
    }

    public function test_c_csv_import_applies_reading_rules(): void
    {
        $alice = $this->user('alice');
        $compteur = Compteur::where('reference', 'CPT-1001')->firstOrFail();
        $existante = $compteur->consommations()->latest('date')->first()->date->toDateString();
        $csv = "date;kwh\n".today()->toDateString().";11,2\n{$existante};9\n".today()->addDay()->toDateString().";3\n";

        $this->actingAs($alice)->post("/compteurs/{$compteur->id}/consommations/import", [
            'fichier' => UploadedFile::fake()->createWithContent('releves.csv', $csv),
        ])->assertSessionHas('erreurs_import', fn ($e) => count($e) === 2);

        $this->assertEquals(11.2, $compteur->consommations()->whereDate('date', today())->value('energie_consommee_kwh'));

        $export = $this->get('/consommations/export?compteur_id='.$compteur->id)->streamedContent();
        $this->assertStringContainsString('CPT-1001', $export);
        $this->assertStringNotContainsString('CPT-1002', $export); // les relevés des autres restent privés
    }

    public function test_c_dashboard_alerts_on_recent_anomalies(): void
    {
        $carol = $this->user('carol');
        $compteur = $carol->compteurs()->firstOrFail();
        Consommation::where('compteur_id', $compteur->id)->whereDate('date', today()->subDays(2))->update(['energie_consommee_kwh' => 60]);

        $this->actingAs($carol)->get('/')->assertOk()->assertSee('Consommation inhabituelle détectée');
    }

    // ====================== Commun : laboratoire IA, API, supervision ======================

    public function test_ai_lab_page_and_retraining(): void
    {
        $this->actingAs($this->user('alice'))->get('/ia')->assertOk()
            ->assertSee('régression logistique')->assertSee('lissage exponentiel de Holt')->assertSee('k-means')
            ->assertDontSee('Réentraîner les modèles');
        $this->post('/ia/entrainer')->assertForbidden();

        $admin = User::where('email', 'admin@solarshare.test')->firstOrFail();
        $this->actingAs($admin)->get('/ia')->assertSee('Réentraîner les modèles');
        $this->post('/ia/entrainer')->assertSessionHas('success');

        $this->artisan('ia:entrainer')->assertExitCode(0)->expectsOutputToContain('profil(s) de consommation');
    }

    public function test_ai_api_endpoints(): void
    {
        $alice = $this->user('alice');
        $token = ['Authorization' => 'Bearer '.$alice->createToken('t')->plainTextToken];
        $toit = Installation::where('nom', 'Toit Alice')->firstOrFail();
        $facture = Facture::whereHas('echange', fn ($q) => $q->where('vendeur_id', $alice->id))->where('statut', 'impayee')->firstOrFail();

        $this->getJson('/api/ia/recommandations', $token)->assertOk()->assertJsonStructure([['offre', 'score', 'raisons']]);
        $this->getJson("/api/ia/installations/{$toit->id}/prevision", $token)->assertOk()
            ->assertJsonStructure(['prevision' => ['historique', 'prevision', 'metriques'], 'suggestion' => ['quantite_kwh', 'prix_kwh']]);
        $this->getJson("/api/ia/factures/{$facture->id}/risque", $token)->assertOk()->assertJsonStructure(['probabilite', 'niveau', 'facteurs']);
        $this->getJson('/api/ia/compteurs/'.$alice->compteurs()->value('id').'/analyse', $token)->assertOk()->assertJsonStructure(['anomalies', 'prevision']);
        $this->getJson('/api/ia/conseils', $token)->assertOk()->assertJsonPath('profil', 'Modéré');

        $this->app['auth']->forgetGuards();
        $bob = ['Authorization' => 'Bearer '.$this->user('bob')->createToken('t')->plainTextToken];
        $this->getJson("/api/ia/factures/{$facture->id}/risque", $bob)->assertForbidden();
    }

    public function test_prometheus_metrics_endpoint_is_protected_by_a_token(): void
    {
        config(['services.metrics.token' => 'secret-de-test']);

        $this->get('/metrics')->assertForbidden();
        $this->get('/metrics', ['Authorization' => 'Bearer mauvais'])->assertForbidden();

        $texte = $this->get('/metrics', ['Authorization' => 'Bearer secret-de-test'])->assertOk()->getContent();
        $this->assertStringContainsString('solarshare_up 1', $texte);
        $this->assertStringContainsString('solarshare_offres{statut="disponible"}', $texte);
        $this->assertMatchesRegularExpression('/solarshare_ia_modele_risque_auc 0\.\d+/', $texte);
    }
}
