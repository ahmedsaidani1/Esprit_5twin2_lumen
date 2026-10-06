<?php

use App\Models\Offre;
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

Schedule::command('offres:expirer')->daily();
