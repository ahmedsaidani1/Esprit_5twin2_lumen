<?php

namespace App\Ai;

/**
 * Détection d'anomalies par score z robuste (Iglewicz & Hoaglin) :
 *   z = 0,6745 × (x − médiane) / MAD
 * La médiane et le MAD ne sont pas faussés par les valeurs extrêmes, contrairement à
 * la moyenne et à l'écart-type. Au-delà de |z| > 3,5, la valeur est jugée anormale.
 *
 * Option : on retire d'abord l'effet du jour de la semaine (on compare chaque lundi aux autres lundis…).
 */
final class DetecteurAnomalies
{
    public function __construct(private float $seuil = 3.5)
    {
    }

    /**
     * @param list<float> $valeurs
     * @param list<int>|null $groupes groupe de chaque valeur (ex. jour de la semaine) pour corriger la saisonnalité
     * @return list<array{index: int, valeur: float, score: float, sens: string}>
     */
    public function detecter(array $valeurs, ?array $groupes = null): array
    {
        $valeurs = array_values($valeurs);
        if (count($valeurs) < 7) {
            return []; // pas assez d'historique pour juger
        }

        $residus = $this->retirerSaisonnalite($valeurs, $groupes);
        $med = Stats::mediane($residus);
        $mad = Stats::mad($residus);
        if ($mad < 1e-9) {
            $mad = Stats::ecartType($residus) / 1.4826 ?: 1e-9; // série quasi constante
        }

        $anomalies = [];
        foreach ($residus as $i => $r) {
            $z = 0.6745 * ($r - $med) / $mad;
            if (abs($z) > $this->seuil) {
                $anomalies[] = ['index' => $i, 'valeur' => $valeurs[$i], 'score' => $z, 'sens' => $z > 0 ? 'pic' : 'chute'];
            }
        }
        return $anomalies;
    }

    private function retirerSaisonnalite(array $valeurs, ?array $groupes): array
    {
        if ($groupes === null) {
            return $valeurs;
        }

        $parGroupe = [];
        foreach ($valeurs as $i => $v) {
            $parGroupe[$groupes[$i]][] = $v;
        }
        $medianes = array_map(fn ($g) => Stats::mediane($g), $parGroupe);
        $globale = Stats::mediane($valeurs);

        return array_map(fn ($v, $i) => $v - $medianes[$groupes[$i]] + $globale, $valeurs, array_keys($valeurs));
    }
}
