<?php

namespace App\Ai;

use InvalidArgumentException;

/**
 * Régression linéaire multiple par moindres carrés : on résout (XᵀX + λI) β = Xᵀy
 * par élimination de Gauss. λ (ridge) stabilise le calcul quand des colonnes se ressemblent.
 */
final class RegressionLineaire
{
    /** @var list<float> coefficients, le premier est l'ordonnée à l'origine */
    private array $beta = [];

    public function __construct(private float $lambda = 1e-6)
    {
    }

    /**
     * @param list<list<float>> $X une ligne par observation
     * @param list<float> $y
     */
    public function entrainer(array $X, array $y): self
    {
        $n = count($X);
        if ($n === 0 || $n !== count($y)) {
            throw new InvalidArgumentException('X et y doivent avoir le même nombre de lignes, non nul.');
        }

        $Xb = array_map(fn ($ligne) => [1.0, ...array_values($ligne)], $X); // colonne de 1 pour l'ordonnée à l'origine
        $p = count($Xb[0]);

        // XᵀX + λI et Xᵀy
        $A = array_fill(0, $p, array_fill(0, $p, 0.0));
        $b = array_fill(0, $p, 0.0);
        foreach ($Xb as $i => $ligne) {
            for ($j = 0; $j < $p; $j++) {
                $b[$j] += $ligne[$j] * $y[$i];
                for ($k = 0; $k < $p; $k++) {
                    $A[$j][$k] += $ligne[$j] * $ligne[$k];
                }
            }
        }
        for ($j = 1; $j < $p; $j++) { // on ne pénalise pas l'ordonnée à l'origine
            $A[$j][$j] += $this->lambda;
        }

        $this->beta = self::resoudre($A, $b);

        return $this;
    }

    /** @param list<float> $x */
    public function predire(array $x): float
    {
        $res = $this->beta[0] ?? 0.0;
        foreach (array_values($x) as $j => $v) {
            $res += ($this->beta[$j + 1] ?? 0.0) * $v;
        }
        return $res;
    }

    /** Coefficient de détermination R² : part de la variance expliquée par le modèle. */
    public function r2(array $X, array $y): float
    {
        $moy = Stats::moyenne($y);
        $ssTot = $ssRes = 0.0;
        foreach ($X as $i => $x) {
            $ssRes += ($y[$i] - $this->predire($x)) ** 2;
            $ssTot += ($y[$i] - $moy) ** 2;
        }
        return $ssTot > 0 ? 1 - $ssRes / $ssTot : 0.0;
    }

    /** @return list<float> */
    public function coefficients(): array
    {
        return $this->beta;
    }

    /** Élimination de Gauss avec pivot partiel. */
    private static function resoudre(array $A, array $b): array
    {
        $n = count($b);
        for ($col = 0; $col < $n; $col++) {
            $pivot = $col;
            for ($l = $col + 1; $l < $n; $l++) {
                if (abs($A[$l][$col]) > abs($A[$pivot][$col])) {
                    $pivot = $l;
                }
            }
            [$A[$col], $A[$pivot]] = [$A[$pivot], $A[$col]];
            [$b[$col], $b[$pivot]] = [$b[$pivot], $b[$col]];

            if (abs($A[$col][$col]) < 1e-12) {
                continue; // colonne dégénérée : coefficient laissé à 0
            }
            for ($l = $col + 1; $l < $n; $l++) {
                $f = $A[$l][$col] / $A[$col][$col];
                for ($k = $col; $k < $n; $k++) {
                    $A[$l][$k] -= $f * $A[$col][$k];
                }
                $b[$l] -= $f * $b[$col];
            }
        }

        $x = array_fill(0, $n, 0.0);
        for ($l = $n - 1; $l >= 0; $l--) {
            if (abs($A[$l][$l]) < 1e-12) {
                continue;
            }
            $s = $b[$l];
            for ($k = $l + 1; $k < $n; $k++) {
                $s -= $A[$l][$k] * $x[$k];
            }
            $x[$l] = $s / $A[$l][$l];
        }
        return $x;
    }
}
