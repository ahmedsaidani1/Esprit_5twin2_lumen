<?php

namespace App\Ai;

use InvalidArgumentException;

/**
 * Prévision de séries temporelles par lissage exponentiel double (méthode de Holt) :
 *   niveau_t    = α·y_t + (1-α)·(niveau_{t-1} + tendance_{t-1})
 *   tendance_t  = β·(niveau_t - niveau_{t-1}) + (1-β)·tendance_{t-1}
 *   prévision_{t+h} = niveau_t + h·tendance_t
 *
 * α et β sont "appris" par recherche sur grille : on garde le couple qui minimise
 * l'erreur des prévisions à un pas sur l'historique.
 */
final class LissageHolt
{
    private float $alpha = 0.5;

    private float $beta = 0.1;

    private float $niveau = 0.0;

    private float $tendance = 0.0;

    private float $ecartResidus = 0.0;

    /** @var list<float> prévisions à un pas sur l'historique (pour mesurer l'erreur) */
    private array $ajustees = [];

    /** @var list<float> */
    private array $serie = [];

    /** @param list<float> $serie */
    public function entrainer(array $serie): self
    {
        if (count($serie) < 4) {
            throw new InvalidArgumentException('Il faut au moins 4 valeurs pour entraîner le modèle.');
        }
        $this->serie = array_values($serie);

        $meilleureErreur = INF;
        foreach (range(0.05, 0.95, 0.05) as $a) {
            foreach ([0.01, 0.05, 0.1, 0.2, 0.3] as $b) {
                [$erreur] = $this->ajuster($this->serie, $a, $b);
                if ($erreur < $meilleureErreur) {
                    [$meilleureErreur, $this->alpha, $this->beta] = [$erreur, $a, $b];
                }
            }
        }

        [, $this->niveau, $this->tendance, $this->ajustees] = $this->ajuster($this->serie, $this->alpha, $this->beta);

        $residus = [];
        foreach ($this->ajustees as $i => $p) {
            $residus[] = $this->serie[$i + 1] - $p;
        }
        $this->ecartResidus = Stats::ecartType($residus);

        return $this;
    }

    /**
     * @return list<array{valeur: float, bas: float, haut: float}> prévision et intervalle à 95 %
     */
    public function prevoir(int $horizon, bool $positif = true): array
    {
        $res = [];
        for ($h = 1; $h <= $horizon; $h++) {
            $v = $this->niveau + $h * $this->tendance;
            $marge = 1.96 * $this->ecartResidus * sqrt($h); // l'incertitude grandit avec l'horizon
            $res[] = [
                'valeur' => $positif ? max(0, $v) : $v,
                'bas' => $positif ? max(0, $v - $marge) : $v - $marge,
                'haut' => $v + $marge,
            ];
        }

        return $res;
    }

    /** @return array{alpha: float, beta: float, mae: float, mape: float} */
    public function metriques(): array
    {
        $reel = array_slice($this->serie, 1);

        return [
            'alpha' => $this->alpha,
            'beta' => $this->beta,
            'mae' => Stats::mae($reel, $this->ajustees),
            'mape' => Stats::mape($reel, $this->ajustees),
        ];
    }

    /** @return array{0: float, 1: float, 2: float, 3: list<float>} [somme des erreurs², niveau, tendance, prévisions à un pas] */
    private function ajuster(array $y, float $a, float $b): array
    {
        $niveau = $y[0];
        $tendance = $y[1] - $y[0];
        $sse = 0.0;
        $prevues = [];
        for ($t = 1, $n = count($y); $t < $n; $t++) {
            $prevision = $niveau + $tendance;
            $prevues[] = $prevision;
            $sse += ($y[$t] - $prevision) ** 2;

            $ancienNiveau = $niveau;
            $niveau = $a * $y[$t] + (1 - $a) * ($niveau + $tendance);
            $tendance = $b * ($niveau - $ancienNiveau) + (1 - $b) * $tendance;
        }

        return [$sse, $niveau, $tendance, $prevues];
    }
}
