<?php

namespace App\Ai;

use InvalidArgumentException;

/**
 * Regroupement non supervisé k-means, initialisé par k-means++.
 * Plusieurs initialisations sont essayées et la meilleure (inertie minimale) est gardée.
 * Une graine fixe rend le résultat reproductible (important pour une démo).
 */
final class KMeans
{
    /** @var list<list<float>> */
    private array $centres = [];
    /** @var list<int> */
    private array $etiquettes = [];
    private float $inertie = INF;

    public function __construct(
        private int $k,
        private int $essais = 10,
        private int $iterationsMax = 100,
        private int $graine = 42,
    ) {
    }

    /** @param list<list<float>> $points (déjà standardisés de préférence) */
    public function entrainer(array $points): self
    {
        if (count($points) < $this->k) {
            throw new InvalidArgumentException("Il faut au moins {$this->k} points pour former {$this->k} groupes.");
        }

        mt_srand($this->graine);
        for ($essai = 0; $essai < $this->essais; $essai++) {
            [$centres, $etiquettes, $inertie] = $this->unEssai($points);
            if ($inertie < $this->inertie) {
                [$this->centres, $this->etiquettes, $this->inertie] = [$centres, $etiquettes, $inertie];
            }
        }
        mt_srand();

        return $this;
    }

    /** @param list<float> $point */
    public function predire(array $point): int
    {
        return self::plusProche($point, $this->centres);
    }

    /** @return list<int> */
    public function etiquettes(): array
    {
        return $this->etiquettes;
    }

    /** @return list<list<float>> */
    public function centres(): array
    {
        return $this->centres;
    }

    public function inertie(): float
    {
        return $this->inertie;
    }

    private function unEssai(array $points): array
    {
        $centres = $this->initialiserPlusPlus($points);
        $etiquettes = [];

        for ($it = 0; $it < $this->iterationsMax; $it++) {
            // 1. affectation de chaque point au centre le plus proche
            $nouvelles = array_map(fn ($p) => self::plusProche($p, $centres), $points);
            if ($nouvelles === $etiquettes) {
                break; // convergence
            }
            $etiquettes = $nouvelles;

            // 2. chaque centre devient la moyenne de ses points
            foreach ($centres as $c => $ancien) {
                $membres = array_values(array_filter($points, fn ($i) => $etiquettes[$i] === $c, ARRAY_FILTER_USE_KEY));
                if ($membres !== []) {
                    $centres[$c] = array_map(fn ($j) => Stats::moyenne(array_column($membres, $j)), array_keys($ancien));
                }
            }
        }

        $inertie = 0.0;
        foreach ($points as $i => $p) {
            $inertie += self::distance2($p, $centres[$etiquettes[$i]]);
        }

        return [$centres, $etiquettes, $inertie];
    }

    /** k-means++ : chaque nouveau centre est tiré avec une probabilité proportionnelle à d² (centres bien répartis). */
    private function initialiserPlusPlus(array $points): array
    {
        $centres = [$points[mt_rand(0, count($points) - 1)]];
        while (count($centres) < $this->k) {
            $d2 = array_map(fn ($p) => min(array_map(fn ($c) => self::distance2($p, $c), $centres)), $points);
            $total = array_sum($d2);
            if ($total <= 0) {
                $centres[] = $points[mt_rand(0, count($points) - 1)];
                continue;
            }
            $tirage = mt_rand() / mt_getrandmax() * $total;
            $cumul = 0.0;
            foreach ($d2 as $i => $d) {
                $cumul += $d;
                if ($cumul >= $tirage) {
                    $centres[] = $points[$i];
                    break;
                }
            }
        }
        return $centres;
    }

    private static function plusProche(array $p, array $centres): int
    {
        $meilleur = 0;
        $dMin = INF;
        foreach ($centres as $c => $centre) {
            $d = self::distance2($p, $centre);
            if ($d < $dMin) {
                [$dMin, $meilleur] = [$d, $c];
            }
        }
        return $meilleur;
    }

    private static function distance2(array $a, array $b): float
    {
        $s = 0.0;
        foreach ($a as $j => $v) {
            $s += ($v - $b[$j]) ** 2;
        }
        return $s;
    }
}
