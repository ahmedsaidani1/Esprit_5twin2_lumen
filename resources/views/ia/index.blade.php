@extends('layouts.app')
@section('title', 'Laboratoire IA')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="mb-0">Laboratoire IA</h2>
    @can('voir-toutes-les-donnees')
        <form action="{{ route('ia.entrainer') }}" method="POST">
            @csrf
            <button class="btn btn-success">Réentraîner les modèles</button>
        </form>
    @endcan
</div>
<p class="text-muted">Toutes les fonctionnalités d'IA sont calculées localement en PHP (aucune API externe) à partir des
    données de la plateforme. Le code des algorithmes est dans <code>app/Ai</code>, leur usage métier dans <code>app/Services/Ia</code>.</p>

{{-- ============ Membre A ============ --}}
<h4 class="mt-4">Échanges et factures</h4>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-body h-100">
            <h5>Risque d'impayé : régression logistique</h5>
            @if($modeleRisque)
                <p class="mb-2">Entraîné le {{ \Carbon\Carbon::parse($modeleRisque['entraine_le'])->format('d/m/Y à H:i') }} sur
                    {{ $modeleRisque['exemples']['total'] }} factures dont l'issue est connue
                    ({{ $modeleRisque['exemples']['impayes'] }} impayées). Évaluation sur {{ $modeleRisque['exemples']['test'] }} factures de test :</p>
                <div class="row text-center mb-2">
                    @foreach(['exactitude' => 'Exactitude', 'precision' => 'Précision', 'rappel' => 'Rappel', 'auc' => 'AUC'] as $cle => $nom)
                        <div class="col"><div class="fs-4">{{ $cle === 'auc' ? number_format($modeleRisque['metriques'][$cle], 2) : round($modeleRisque['metriques'][$cle] * 100) . ' %' }}</div>
                            <div class="small text-muted">{{ $nom }}</div></div>
                    @endforeach
                </div>
                <p class="small mb-1">Variables utilisées (connues à la date d'émission, sans information du futur) :</p>
                <ul class="small mb-0">@foreach($variablesRisque as $v)<li>{{ $v }}</li>@endforeach</ul>
            @else
                <p class="mb-0">Modèle pas encore entraîné : lancez <code>php artisan ia:entrainer</code> (il faut au moins
                    {{ config('ia.risque.minimum_exemples') }} factures dont l'issue est connue).</p>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-body h-100">
            <h5>Offres recommandées pour vous</h5>
            <p class="small text-muted">Système de recommandation par le contenu : votre historique d'achats (prix et
                quantités habituels), votre communauté et la fiabilité des vendeurs.</p>
            @forelse($recommandations as $r)
                <div class="border rounded p-2 mb-2">
                    <a href="{{ route('offres.show', $r['offre']) }}">{{ $r['offre']->installation->nom }}</a> :
                    {{ number_format($r['offre']->quantite_kwh, 0) }} kWh à {{ number_format($r['offre']->prix_kwh, 4) }} DT
                    <span class="badge bg-success">{{ round($r['score'] * 100) }} %</span>
                    <div class="small text-muted">{{ implode(' · ', $r['raisons']) }}</div>
                </div>
            @empty
                <p class="mb-0">Aucune offre disponible à recommander.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ============ Membre B ============ --}}
<h4>Production et offres</h4>
<div class="card card-body mb-4">
    <h5>Prévision de production : lissage exponentiel de Holt</h5>
    <p class="small text-muted">Pour chaque installation, α (niveau) et β (tendance) sont choisis par recherche sur grille
        en minimisant l'erreur des prévisions à un jour sur l'historique. La suggestion d'offre combine cette prévision,
        l'autoconsommation mesurée par le compteur lié et le prix du marché (offre / demande).</p>
    @if($previsions->isNotEmpty())
        <table class="table table-sm mb-0">
            <thead><tr><th>Installation</th><th>α</th><th>β</th><th>Erreur moyenne (MAE)</th><th>Erreur relative (MAPE)</th><th>Prévu sur 7 jours</th></tr></thead>
            <tbody>
            @foreach($previsions as $p)
                <tr>
                    <td><a href="{{ route('installations.show', $p['installation']) }}">{{ $p['installation']->nom }}</a></td>
                    <td>{{ $p['resultat']['metriques']['alpha'] }}</td>
                    <td>{{ $p['resultat']['metriques']['beta'] }}</td>
                    <td>{{ number_format($p['resultat']['metriques']['mae'], 1) }} kWh</td>
                    <td>{{ number_format($p['resultat']['metriques']['mape'], 0) }} %</td>
                    <td>{{ number_format(array_sum(array_column($p['resultat']['prevision'], 'kwh')), 0, ',', ' ') }} kWh</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="mb-0">Aucune de vos installations n'a encore assez d'historique ({{ config('ia.prevision.minimum_jours') }} jours).</p>
    @endif
</div>

{{-- ============ Membre C ============ --}}
<h4>Compteurs et consommation</h4>
<div class="card card-body mb-4">
    <h5>Profils de consommation : k-means · anomalies : score z robuste · prévision : régression linéaire</h5>
    @if($groupes)
        <table class="table table-sm">
            <thead><tr><th>Profil</th><th>Membres</th><th>Moyenne (kWh/jour)</th><th>Variabilité</th><th>Week-end / moyenne</th></tr></thead>
            <tbody>
            @foreach($groupes as $g)
                <tr><td>{{ $g['profil'] }}</td><td>{{ $g['membres'] }}</td><td>{{ $g['moyenne'] }}</td><td>{{ $g['variabilite'] }}</td><td>{{ $g['weekend'] }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p>Pas encore assez de consommateurs avec un historique pour former les groupes.</p>
    @endif
    <a href="{{ route('analyse.consommation') }}" class="btn btn-outline-success align-self-start">Voir mon analyse et mes conseils</a>
</div>
@endsection
