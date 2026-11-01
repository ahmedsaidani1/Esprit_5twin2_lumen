<?php

namespace Tests\Unit;

use App\Ai\DetecteurAnomalies;
use App\Ai\KMeans;
use App\Ai\LissageHolt;
use App\Ai\RegressionLineaire;
use App\Ai\RegressionLogistique;
use App\Ai\Stats;
use PHPUnit\Framework\TestCase;

/** Vérifie que chaque algorithme retrouve un résultat connu sur des données construites. */
class AiAlgorithmsTest extends TestCase
{
    public function test_stats(): void
    {
        $x = [1, 2, 3, 4, 100];
        $this->assertSame(22.0, Stats::moyenne($x));
        $this->assertSame(3.0, Stats::mediane($x)); // la médiane ignore la valeur extrême
        $this->assertSame(1.0, Stats::mad($x));
        $this->assertSame(2.0, Stats::quantile([1, 2, 3], 0.5));
        $this->assertEqualsWithDelta(1.0, Stats::mae([1, 2, 3], [2, 3, 4]), 1e-9);
        $this->assertEqualsWithDelta(50.0, Stats::mape([2, 4], [1, 2]), 1e-9);
    }

    public function test_linear_regression_recovers_known_coefficients(): void
    {
        // y = 3 + 2·x1 − 0,5·x2
        $X = $y = [];
        for ($i = 0; $i < 50; $i++) {
            $x1 = $i;
            $x2 = ($i * 7) % 11;
            $X[] = [$x1, $x2];
            $y[] = 3 + 2 * $x1 - 0.5 * $x2;
        }
        $m = (new RegressionLineaire())->entrainer($X, $y);

        [$b0, $b1, $b2] = $m->coefficients();
        $this->assertEqualsWithDelta(3.0, $b0, 1e-3);
        $this->assertEqualsWithDelta(2.0, $b1, 1e-3);
        $this->assertEqualsWithDelta(-0.5, $b2, 1e-3);
        $this->assertEqualsWithDelta(1.0, $m->r2($X, $y), 1e-6);
        $this->assertEqualsWithDelta(3 + 2 * 60 - 0.5 * 4, $m->predire([60, 4]), 1e-3);
    }

    public function test_logistic_regression_separates_two_classes(): void
    {
        // classe 1 quand x1 + x2 > 10
        $X = $y = [];
        for ($a = 0; $a <= 10; $a++) {
            for ($b = 0; $b <= 10; $b++) {
                $X[] = [$a, $b];
                $y[] = $a + $b > 10 ? 1 : 0;
            }
        }
        $m = (new RegressionLogistique(0.5, 1500, 0.0))->entrainer($X, $y);
        $metriques = $m->evaluer($X, $y);

        $this->assertGreaterThan(0.9, $metriques['exactitude']);
        $this->assertGreaterThan(0.95, $metriques['auc']);
        $this->assertGreaterThan(0.9, $m->probabilite([10, 10]));
        $this->assertLessThan(0.1, $m->probabilite([0, 0]));

        // sauvegarde / rechargement : mêmes prédictions
        $copie = RegressionLogistique::depuisTableau($m->versTableau());
        $this->assertEqualsWithDelta($m->probabilite([6, 6]), $copie->probabilite([6, 6]), 1e-12);
    }

    public function test_kmeans_finds_three_obvious_groups_reproducibly(): void
    {
        $points = [];
        foreach ([[0, 0], [10, 10], [0, 10]] as [$cx, $cy]) {
            for ($i = 0; $i < 10; $i++) {
                $points[] = [$cx + ($i % 3) * 0.1, $cy + intdiv($i, 3) * 0.1];
            }
        }
        $km = (new KMeans(3))->entrainer($points);
        $etiquettes = $km->etiquettes();

        // les 10 points de chaque paquet ont la même étiquette, et les 3 paquets des étiquettes différentes
        foreach ([0, 10, 20] as $debut) {
            $this->assertCount(1, array_unique(array_slice($etiquettes, $debut, 10)));
        }
        $this->assertCount(3, array_unique($etiquettes));
        $this->assertSame($etiquettes, (new KMeans(3))->entrainer($points)->etiquettes()); // graine fixe
        $this->assertSame($km->predire([10.2, 9.9]), $etiquettes[10]);
    }

    public function test_holt_forecasts_a_linear_trend(): void
    {
        $serie = array_map(fn ($t) => 20 + 1.5 * $t, range(0, 29)); // +1,5 par jour
        $m = (new LissageHolt())->entrainer($serie);
        $prev = $m->prevoir(3);

        $this->assertEqualsWithDelta(20 + 1.5 * 30, $prev[0]['valeur'], 0.5);
        $this->assertEqualsWithDelta(20 + 1.5 * 32, $prev[2]['valeur'], 0.5);
        $this->assertLessThan(1.0, $m->metriques()['mape']);
        $this->assertLessThanOrEqual($prev[0]['valeur'], $prev[0]['haut']);
    }

    public function test_anomaly_detector_flags_spikes_but_not_weekly_pattern(): void
    {
        // week-end deux fois plus élevé (normal), plus un pic anormal au jour 20 et une chute au jour 30
        $valeurs = $jours = [];
        for ($i = 0; $i < 42; $i++) {
            $jour = $i % 7;
            $jours[] = $jour;
            $valeurs[] = ($jour >= 5 ? 20 : 10) + ($i % 3) * 0.3;
        }
        $valeurs[20] = 45;
        $valeurs[30] = 0.5;

        $anomalies = (new DetecteurAnomalies())->detecter($valeurs, $jours);
        $indices = array_column($anomalies, 'index');

        $this->assertContains(20, $indices);
        $this->assertContains(30, $indices);
        $this->assertCount(2, $anomalies); // les week-ends ne sont pas pris pour des anomalies
        $this->assertSame('pic', $anomalies[array_search(20, $indices)]['sens']);
    }
}
