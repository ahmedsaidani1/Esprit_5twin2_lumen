<?php

use App\Models\Offre;
use App\Services\Ia\AnalyseConsommationService;
use App\Services\Ia\RisqueImpayeService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('offres:expirer', function () {
    $n = Offre::expireOutdated();
    $this->info("{$n} offre(s) passée(s) en « expiree ».");
})->purpose('Expire les offres disponibles dont la date de fin est dépassée');

Artisan::command('ia:entrainer', function (RisqueImpayeService $risque, AnalyseConsommationService $consommation) {
    $resultat = $risque->entrainer();
    $consommation->oublierProfils();
    $groupes = $consommation->profils()['groupes'];

    if (! $resultat['entraine']) {
        $this->warn($resultat['raison']);
    } else {
        $this->info(sprintf('Risque d\'impayé : %d factures (%d impayées), entraînement %d / test %d.',
            $resultat['exemples']['total'], $resultat['exemples']['impayes'], $resultat['exemples']['entrainement'], $resultat['exemples']['test']));
        $this->table(['Exactitude', 'Précision', 'Rappel', 'AUC'], [array_map(fn ($v) => number_format($v, 3), $resultat['metriques'])]);
    }
    $this->info(count($groupes).' profil(s) de consommation calculé(s).');
})->purpose('Entraîne les modèles d\'IA (risque d\'impayé, profils de consommation)');

Schedule::command('offres:expirer')->daily();
Schedule::command('ia:entrainer')->dailyAt('03:00');
