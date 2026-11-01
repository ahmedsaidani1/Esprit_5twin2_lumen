<?php

namespace Database\Seeders;

use App\Models\Communaute;
use App\Models\Compteur;
use App\Models\Consommation;
use App\Models\Echange;
use App\Models\Facture;
use App\Models\Installation;
use App\Models\Offre;
use App\Models\Production;
use App\Models\User;
use App\Services\Ia\AnalyseConsommationService;
use App\Services\Ia\RisqueImpayeService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration réalistes sur 90 jours, pour que les fonctionnalités d'IA aient
 * de quoi apprendre : production solaire qui dépend de la météo, consommateurs aux profils
 * différents (avec quelques anomalies), historique d'échanges où certains acheteurs paient mal.
 *
 *   php artisan migrate:fresh --seed --seeder=DemoSeeder
 *
 * Tous les comptes ont le mot de passe "password".
 */
class DemoSeeder extends Seeder
{
    private const JOURS = 90;

    public function run(): void
    {
        mt_srand(2026); // données identiques à chaque exécution
        $aujourdhui = CarbonImmutable::today();

        $tunis = Communaute::create(['nom' => 'Communauté Solaire Tunis', 'ville' => 'Tunis']);
        $sfax = Communaute::create(['nom' => 'Communauté Éolienne Sfax', 'ville' => 'Sfax']);
        $sousse = Communaute::create(['nom' => 'Communauté Verte Sousse', 'ville' => 'Sousse']);

        $creer = fn (string $nom, string $email, ?Communaute $c, int $ancienneteJours) => tap(
            User::factory()->create(['name' => $nom, 'email' => $email, 'communaute_id' => $c?->id]),
            fn ($u) => $u->forceFill(['created_at' => $aujourdhui->subDays($ancienneteJours)])->save()
        );

        User::factory()->admin()->create(['name' => 'Admin SolarShare', 'email' => 'admin@solarshare.test']);
        $alice = $creer('Alice Ben Salah', 'alice@solarshare.test', $tunis, 400);
        $bob = $creer('Bob Trabelsi', 'bob@solarshare.test', $tunis, 300);
        $carol = $creer('Carol Haddad', 'carol@solarshare.test', $sousse, 250);
        $driss = $creer('Driss Gharbi', 'driss@solarshare.test', $sfax, 500);
        $emna = $creer('Emna Jlassi', 'emna@solarshare.test', $sousse, 200);
        $farah = $creer('Farah Mansour', 'farah@solarshare.test', $sfax, 120);
        $test = $creer('Test User', 'test@example.com', $tunis, 150);

        // ---------- Installations et production (dépend de l'ensoleillement du jour) ----------
        $installations = [
            [$alice, $tunis, 'Toit Alice', 'solaire', 12.0, 'La Marsa'],
            [$driss, $sfax, 'Ferme solaire Driss', 'solaire', 60.0, 'Sfax'],
            [$emna, $sousse, 'Toit Emna', 'solaire', 8.0, 'Sousse'],
            [$test, $tunis, 'Toit solaire Ariana', 'solaire', 12.5, 'Ariana'],
        ];
        $meteo = [];
        for ($j = 0; $j < self::JOURS; $j++) {
            $meteo[$j] = mt_rand(0, 100) < 25 ? mt_rand(30, 70) / 100 : mt_rand(85, 105) / 100; // 1 jour sur 4 nuageux
        }
        $parInstallation = [];
        foreach ($installations as [$proprio, $comm, $nom, $type, $kw, $lieu]) {
            $inst = Installation::create(['user_id' => $proprio->id, 'communaute_id' => $comm->id, 'nom' => $nom,
                'type' => $type, 'puissance_kw' => $kw, 'localisation' => $lieu]);
            $parInstallation[$nom] = $inst;
            $lignes = [];
            for ($j = self::JOURS; $j >= 1; $j--) {
                $saison = 0.85 + 0.25 * ($j / self::JOURS); // l'automne réduit peu à peu l'ensoleillement
                $kwh = $kw * 4.6 * $saison * $meteo[$j - 1] * (mt_rand(95, 105) / 100);
                $lignes[] = ['installation_id' => $inst->id, 'date' => $aujourdhui->subDays($j)->toDateString(),
                    'energie_produite_kwh' => round($kwh, 2), 'created_at' => now(), 'updated_at' => now()];
            }
            Production::insert($lignes);
        }

        // ---------- Compteurs et consommation (profils différents, quelques anomalies) ----------
        $consommateurs = [
            // [utilisateur, installation liée, kWh/jour, facteur week-end, tendance/jour, anomalies [jour => facteur]]
            [$alice, 'Toit Alice', 11, 1.2, 0.01, []],
            [$bob, null, 24, 1.1, 0.06, [12 => 3.2, 40 => 2.8]],     // intensif, en hausse, deux pics
            [$carol, null, 6, 1.6, 0.0, [25 => 0.05]],               // sobre, surtout le week-end, une chute
            [$driss, 'Ferme solaire Driss', 30, 1.0, 0.0, []],
            [$emna, 'Toit Emna', 7, 1.1, -0.02, [60 => 3.5]],
            [$farah, null, 14, 1.3, 0.02, []],
            [$test, 'Toit solaire Ariana', 12, 1.2, 0.0, []],
        ];
        foreach ($consommateurs as $n => [$user, $instNom, $base, $weekend, $tendance, $anomalies]) {
            $compteur = Compteur::create([
                'user_id' => $user->id,
                'installation_id' => $instNom ? $parInstallation[$instNom]->id : null,
                'reference' => sprintf('CPT-%04d', 1001 + $n),
                'date_installation' => $aujourdhui->subDays(400)->toDateString(),
                'statut' => 'actif',
            ]);
            $lignes = [];
            for ($j = self::JOURS; $j >= 1; $j--) {
                $date = $aujourdhui->subDays($j);
                $kwh = ($base + $tendance * (self::JOURS - $j)) * ($date->isWeekend() ? $weekend : 1) * (mt_rand(88, 112) / 100);
                $kwh *= $anomalies[$j] ?? 1;
                $lignes[] = ['compteur_id' => $compteur->id, 'date' => $date->toDateString(),
                    'energie_consommee_kwh' => round($kwh, 2), 'created_at' => now(), 'updated_at' => now()];
            }
            Consommation::insert($lignes);
        }
        Compteur::create(['user_id' => $farah->id, 'reference' => 'CPT-0999', 'statut' => 'en_panne',
            'date_installation' => $aujourdhui->subYears(3)->toDateString()]);

        // ---------- Historique d'échanges et de factures (fiabilité de paiement différente selon l'acheteur) ----------
        $vendeurs = [[$alice, $tunis], [$driss, $sfax], [$emna, $sousse], [$test, $tunis]];
        $acheteurs = [ // [acheteur, probabilité de payer à temps]
            [$bob, 0.95], [$carol, 0.85], [$farah, 0.35], [$alice, 0.9], [$emna, 0.75], [$test, 0.8],
        ];
        for ($i = 0; $i < 170; $i++) {
            [$vendeur, $comm] = $vendeurs[mt_rand(0, count($vendeurs) - 1)];
            [$acheteur, $fiabilite] = $acheteurs[mt_rand(0, count($acheteurs) - 1)];
            if ($acheteur->is($vendeur)) {
                continue;
            }
            $date = $aujourdhui->subDays(mt_rand(5, self::JOURS))->setTime(mt_rand(8, 19), 0);
            $quantite = mt_rand(10, 120);
            $prix = mt_rand(2000, 3200) / 10000;
            $statut = mt_rand(1, 100) <= 12 ? 'refuse' : (mt_rand(0, 1) ? 'termine' : 'accepte');

            $echange = Echange::create([
                'vendeur_id' => $vendeur->id, 'acheteur_id' => $acheteur->id, 'communaute_id' => $comm->id,
                'quantite_kwh' => $quantite, 'prix_unitaire' => $prix, 'date_echange' => $date, 'statut' => $statut,
            ]);
            if ($statut === 'refuse') {
                continue;
            }

            $delai = [15, 30][mt_rand(0, 1)];
            $echeance = $date->addDays($delai);
            // plus le montant est élevé et le délai court, plus le risque de retard augmente
            $pPayer = $fiabilite - ($quantite * $prix > 25 ? 0.15 : 0) - ($delai === 15 ? 0.05 : 0);
            $statutFacture = $echeance->isFuture()
                ? (mt_rand(1, 100) <= 40 ? 'payee' : 'impayee')
                : (mt_rand(1, 100) / 100 <= $pPayer ? 'payee' : 'impayee');

            Facture::create([
                'echange_id' => $echange->id, 'montant_total' => round($quantite * $prix, 2),
                'date_emission' => $date->toDateString(), 'date_echeance' => $echeance->toDateString(), 'statut' => $statutFacture,
            ]);
        }

        // ---------- Offres en cours ----------
        foreach ([['Toit Alice', 40, 0.27], ['Ferme solaire Driss', 250, 0.24], ['Toit Emna', 25, 0.29], ['Toit solaire Ariana', 35, 0.26]] as [$nom, $q, $p]) {
            Offre::create(['installation_id' => $parInstallation[$nom]->id, 'quantite_kwh' => $q, 'prix_kwh' => $p,
                'date_debut' => $aujourdhui->toDateString(), 'date_fin' => $aujourdhui->addDays(20)->toDateString(), 'statut' => 'disponible']);
        }
        Offre::create(['installation_id' => $parInstallation['Toit Emna']->id, 'quantite_kwh' => 20, 'prix_kwh' => 0.30,
            'date_debut' => $aujourdhui->subDays(30)->toDateString(), 'date_fin' => $aujourdhui->subDays(10)->toDateString(), 'statut' => 'expiree']);

        mt_srand();

        // ---------- Entraînement des modèles ----------
        $resultat = app(RisqueImpayeService::class)->entrainer();
        app(AnalyseConsommationService::class)->oublierProfils();
        $this->command?->info($resultat['entraine']
            ? sprintf('Modèle de risque entraîné sur %d factures (AUC %.2f).', $resultat['exemples']['total'], $resultat['metriques']['auc'])
            : 'Modèle de risque non entraîné : '.$resultat['raison']);
    }
}
