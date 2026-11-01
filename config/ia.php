<?php

/*
 * Paramètres des fonctionnalités d'IA (toutes calculées localement, sans API externe).
 */
return [

    // Prix de l'électricité du réseau (DT/kWh), pour estimer les économies d'un membre
    'tarif_reseau_kwh' => (float) env('IA_TARIF_RESEAU_KWH', 0.35),

    // Prix de référence si le marché n'a pas encore d'historique
    'prix_defaut_kwh' => 0.25,

    // Membre A : risque d'impayé (régression logistique)
    'risque' => [
        'fichier' => 'ia/risque-impaye.json',  // dans storage/app
        'minimum_exemples' => 20,
        'seuils' => ['moyen' => 0.30, 'eleve' => 0.60],
    ],

    // Membre B : prévision de production (lissage de Holt)
    'prevision' => [
        'historique_jours' => 60,
        'horizon_jours' => 7,
        'minimum_jours' => 14,
        'autoconsommation_defaut' => 0.30, // part de la production consommée sur place si aucun compteur n'est lié
    ],

    // Membre C : analyse de consommation
    'consommation' => [
        'historique_jours' => 90,
        'horizon_jours' => 30,
        'seuil_anomalie' => 3.5,  // score z robuste
        'groupes' => 3,           // nombre de profils k-means
    ],
];
