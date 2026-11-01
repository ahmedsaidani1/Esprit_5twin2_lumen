<?php

namespace App\Ai;

use InvalidArgumentException;

/**
 * Classification binaire par régression logistique, entraînée par descente de gradient
 * avec régularisation L2. Les variables sont standardisées pendant l'entraînement.
 *
 * P(y = 1 | x) = σ(w·z + b), avec z = x standardisé et σ(t) = 1 / (1 + e^-t)
 */
final class RegressionLogistique
{
    /** @var list<float> */
    private array $poids = [];

    private float $biais = 0.0;

    /** @var list<float> */
    private array $moyennes = [];

    /** @var list<float> */
    private array $ecarts = [];

    public function __construct(
        private float $tauxApprentissage = 0.1,
        private int $iterations = 2000,
        private float $l2 = 0.01,
        // Classes déséquilibrées (ex. 25 % d'impayés) : chaque classe pèse autant dans l'apprentissage,
        // sinon le modèle prédit presque toujours la classe majoritaire.
        private bool $equilibrerClasses = false,
    ) {}

    /**
     * @param  list<list<float>>  $X
     * @param  list<int>  $y  0 ou 1
     */
    public function entrainer(array $X, array $y): self
    {
        $n = count($X);
        if ($n === 0 || $n !== count($y)) {
            throw new InvalidArgumentException('X et y doivent avoir le même nombre de lignes, non nul.');
        }

        [$Z, $this->moyennes, $this->ecarts] = Stats::standardiser($X);
        $p = count($Z[0]);
        $this->poids = array_fill(0, $p, 0.0);
        $this->biais = 0.0;

        // poids de chaque exemple : n / (2 × effectif de sa classe) si on équilibre, sinon 1
        $effectifs = array_count_values($y);
        $poidsExemple = array_map(
            fn ($c) => $this->equilibrerClasses && count($effectifs) === 2 ? $n / (2 * $effectifs[$c]) : 1.0,
            $y
        );

        for ($it = 0; $it < $this->iterations; $it++) {
            $gradW = array_fill(0, $p, 0.0);
            $gradB = 0.0;
            foreach ($Z as $i => $z) {
                $erreur = (self::sigmoide($this->score($z)) - $y[$i]) * $poidsExemple[$i];
                for ($j = 0; $j < $p; $j++) {
                    $gradW[$j] += $erreur * $z[$j];
                }
                $gradB += $erreur;
            }
            for ($j = 0; $j < $p; $j++) {
                $this->poids[$j] -= $this->tauxApprentissage * ($gradW[$j] / $n + $this->l2 * $this->poids[$j]);
            }
            $this->biais -= $this->tauxApprentissage * $gradB / $n;
        }

        return $this;
    }

    /** Probabilité de la classe 1. @param list<float> $x */
    public function probabilite(array $x): float
    {
        return self::sigmoide($this->score($this->standardiserLigne($x)));
    }

    /**
     * Contribution de chaque variable au score (poids × valeur standardisée) :
     * positive = pousse vers la classe 1. Sert à expliquer une prédiction.
     *
     * @return list<float>
     */
    public function contributions(array $x): array
    {
        $z = $this->standardiserLigne($x);

        return array_map(fn ($w, $v) => $w * $v, $this->poids, $z);
    }

    /**
     * Métriques sur un jeu de test : exactitude, précision, rappel, AUC.
     *
     * @return array{exactitude: float, precision: float, rappel: float, auc: float}
     */
    public function evaluer(array $X, array $y, float $seuil = 0.5): array
    {
        $vp = $fp = $vn = $fn = 0;
        $scores = [];
        foreach ($X as $i => $x) {
            $p = $this->probabilite($x);
            $scores[] = [$p, $y[$i]];
            $prediction = $p >= $seuil ? 1 : 0;
            match (true) {
                $prediction === 1 && $y[$i] === 1 => $vp++,
                $prediction === 1 && $y[$i] === 0 => $fp++,
                $prediction === 0 && $y[$i] === 0 => $vn++,
                default => $fn++,
            };
        }
        $total = max(1, count($X));

        return [
            'exactitude' => ($vp + $vn) / $total,
            'precision' => $vp + $fp > 0 ? $vp / ($vp + $fp) : 0.0,
            'rappel' => $vp + $fn > 0 ? $vp / ($vp + $fn) : 0.0,
            'auc' => self::auc($scores),
        ];
    }

    public function versTableau(): array
    {
        return ['poids' => $this->poids, 'biais' => $this->biais, 'moyennes' => $this->moyennes, 'ecarts' => $this->ecarts];
    }

    public static function depuisTableau(array $d): self
    {
        $m = new self;
        $m->poids = $d['poids'];
        $m->biais = $d['biais'];
        $m->moyennes = $d['moyennes'];
        $m->ecarts = $d['ecarts'];

        return $m;
    }

    public static function sigmoide(float $t): float
    {
        return 1 / (1 + exp(-max(-35, min(35, $t))));
    }

    private function score(array $z): float
    {
        $s = $this->biais;
        foreach ($z as $j => $v) {
            $s += $this->poids[$j] * $v;
        }

        return $s;
    }

    private function standardiserLigne(array $x): array
    {
        return array_map(fn ($v, $j) => ($v - $this->moyennes[$j]) / $this->ecarts[$j], array_values($x), array_keys(array_values($x)));
    }

    /** Aire sous la courbe ROC : probabilité qu'un positif soit mieux classé qu'un négatif. */
    private static function auc(array $scores): float
    {
        $positifs = array_filter($scores, fn ($s) => $s[1] === 1);
        $negatifs = array_filter($scores, fn ($s) => $s[1] === 0);
        if ($positifs === [] || $negatifs === []) {
            return 0.5;
        }
        $gagnes = 0.0;
        foreach ($positifs as [$sp]) {
            foreach ($negatifs as [$sn]) {
                $gagnes += $sp > $sn ? 1 : ($sp == $sn ? 0.5 : 0);
            }
        }

        return $gagnes / (count($positifs) * count($negatifs));
    }
}
