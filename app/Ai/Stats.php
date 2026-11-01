<?php

namespace App\Ai;

/** Statistiques de base utilisées par tous les modèles. */
final class Stats
{
    /** @param list<float> $x */
    public static function moyenne(array $x): float
    {
        return $x === [] ? 0.0 : array_sum($x) / count($x);
    }

    /** Écart-type (échantillon, n-1). @param list<float> $x */
    public static function ecartType(array $x): float
    {
        $n = count($x);
        if ($n < 2) {
            return 0.0;
        }
        $m = self::moyenne($x);
        $somme = 0.0;
        foreach ($x as $v) {
            $somme += ($v - $m) ** 2;
        }

        return sqrt($somme / ($n - 1));
    }

    /** @param list<float> $x */
    public static function mediane(array $x): float
    {
        return self::quantile($x, 0.5);
    }

    /** Quantile par interpolation linéaire (q entre 0 et 1). @param list<float> $x */
    public static function quantile(array $x, float $q): float
    {
        if ($x === []) {
            return 0.0;
        }
        sort($x);
        $pos = ($q * (count($x) - 1));
        $bas = (int) floor($pos);
        $haut = (int) ceil($pos);

        return $x[$bas] + ($x[$haut] - $x[$bas]) * ($pos - $bas);
    }

    /** Écart absolu médian (MAD) : une dispersion insensible aux valeurs extrêmes. @param list<float> $x */
    public static function mad(array $x): float
    {
        $med = self::mediane($x);

        return self::mediane(array_map(fn ($v) => abs($v - $med), $x));
    }

    /** Erreur absolue moyenne. @param list<float> $reel @param list<float> $prevu */
    public static function mae(array $reel, array $prevu): float
    {
        $n = min(count($reel), count($prevu));
        if ($n === 0) {
            return 0.0;
        }
        $somme = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $somme += abs($reel[$i] - $prevu[$i]);
        }

        return $somme / $n;
    }

    /** Erreur absolue moyenne en pourcentage (les valeurs réelles nulles sont ignorées). */
    public static function mape(array $reel, array $prevu): float
    {
        $erreurs = [];
        foreach ($reel as $i => $r) {
            if (abs($r) > 1e-9 && isset($prevu[$i])) {
                $erreurs[] = abs(($r - $prevu[$i]) / $r);
            }
        }

        return $erreurs === [] ? 0.0 : 100 * self::moyenne($erreurs);
    }

    /**
     * Standardisation colonne par colonne : (x - moyenne) / écart-type.
     *
     * @param  list<list<float>>  $X
     * @return array{0: list<list<float>>, 1: list<float>, 2: list<float>} [X standardisé, moyennes, écarts-types]
     */
    public static function standardiser(array $X): array
    {
        $nbCol = count($X[0] ?? []);
        $moyennes = $ecarts = [];
        for ($j = 0; $j < $nbCol; $j++) {
            $col = array_column($X, $j);
            $moyennes[$j] = self::moyenne($col);
            $ecarts[$j] = self::ecartType($col) ?: 1.0; // colonne constante : pas de division par zéro
        }

        return [self::appliquerStandardisation($X, $moyennes, $ecarts), $moyennes, $ecarts];
    }

    /** @param list<list<float>> $X */
    public static function appliquerStandardisation(array $X, array $moyennes, array $ecarts): array
    {
        return array_map(
            fn ($ligne) => array_map(fn ($v, $j) => ($v - $moyennes[$j]) / $ecarts[$j], $ligne, array_keys($ligne)),
            $X
        );
    }
}
