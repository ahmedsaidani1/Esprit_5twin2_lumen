@extends('layouts.app')
@section('title', 'Ma consommation')
@section('content')
<h2 class="mb-3">Ma consommation : analyse IA</h2>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-body h-100 text-center">
            <div class="text-muted small">Votre profil</div>
            <div class="display-6">{{ $analyse['profil'] ?? '—' }}</div>
            <div class="small text-muted">déterminé par regroupement k-means</div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-body h-100">
            <h5>Économies possibles le mois prochain</h5>
            @if($analyse['economies'])
                <p class="mb-1">Consommation prévue : <strong>{{ number_format($analyse['economies']['kwh_prevus'], 0, ',', ' ') }} kWh</strong>.</p>
                <p class="mb-1">Au tarif du réseau : {{ number_format($analyse['economies']['cout_reseau'], 2, ',', ' ') }} DT ;
                    au prix moyen du marché SolarShare : {{ number_format($analyse['economies']['cout_marche'], 2, ',', ' ') }} DT.</p>
                <p class="mb-0 text-success"><strong>Économie estimée : {{ number_format($analyse['economies']['economie'], 2, ',', ' ') }} DT</strong>
                    en achetant l'énergie de votre communauté.</p>
            @else
                <p class="mb-0">Ajoutez un compteur et au moins deux semaines de relevés pour obtenir une estimation.</p>
            @endif
        </div>
    </div>
</div>

<div class="card card-body mb-4">
    <h5>Conseils personnalisés</h5>
    <ul class="mb-0">
        @foreach($analyse['conseils'] as $c)<li>{{ $c }}</li>@endforeach
    </ul>
</div>

<div class="card card-body mb-4">
    <h5>Les profils de la communauté</h5>
    <p class="small text-muted">Chaque consommateur ayant au moins 14 relevés est décrit par trois mesures (consommation
        moyenne par jour, variabilité, part du week-end), standardisées puis regroupées par k-means (k = {{ config('ia.consommation.groupes') }},
        initialisation k-means++). Les groupes sont nommés du moins au plus consommateur.</p>
    @if($groupes)
        <table class="table table-sm mb-0">
            <thead><tr><th>Profil</th><th>Membres</th><th>Moyenne (kWh/jour)</th><th>Variabilité</th><th>Week-end / moyenne</th></tr></thead>
            <tbody>
            @foreach($groupes as $g)
                <tr @class(['table-success' => $g['profil'] === $analyse['profil']])>
                    <td>{{ $g['profil'] }} @if($g['profil'] === $analyse['profil'])<span class="badge bg-success">vous</span>@endif</td>
                    <td>{{ $g['membres'] }}</td><td>{{ $g['moyenne'] }}</td><td>{{ $g['variabilite'] }}</td><td>{{ $g['weekend'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="mb-0">Pas encore assez de consommateurs avec un historique pour former les groupes.</p>
    @endif
</div>

<h5>Mes compteurs</h5>
<ul>
    @forelse($compteurs as $c)
        <li><a href="{{ route('compteurs.show', $c) }}">{{ $c->reference }}</a> : {{ $c->consommations_count }} relevé(s), {{ str_replace('_', ' ', $c->statut) }}</li>
    @empty
        <li>Aucun compteur. <a href="{{ route('compteurs.create') }}">En ajouter un</a>.</li>
    @endforelse
</ul>
@endsection
