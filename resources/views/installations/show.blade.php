@extends('layouts.app')
@section('title', 'Détail installation')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">{{ $installation->nom }}</h2>
    @can('update', $installation)
        <a href="{{ route('installations.edit', $installation) }}" class="btn btn-outline-secondary">Modifier</a>
    @endcan
</div>
<div class="card card-body mb-4">
    <div class="row">
        <div class="col-md-3"><strong>Propriétaire :</strong> {{ $installation->proprietaire->name }}</div>
        <div class="col-md-3"><strong>Communauté :</strong> {{ $installation->communaute->nom ?? '—' }}</div>
        <div class="col-md-2"><strong>Type :</strong> {{ ucfirst($installation->type) }}</div>
        <div class="col-md-2"><strong>Puissance :</strong> {{ number_format($installation->puissance_kw, 2) }} kW</div>
        <div class="col-md-2"><strong>Lieu :</strong> {{ $installation->localisation ?? '—' }}</div>
    </div>
</div>

{{-- Membre B — IA : prévision de production --}}
@if($prevision)
    @include('partials.graphique-serie', [
        'id' => 'graphique-production',
        'titre' => 'Production des derniers jours et prévision IA sur ' . count($prevision['prevision']) . ' jours',
        'libelle' => 'Production',
        'historique' => $prevision['historique'],
        'prevision' => $prevision['prevision'],
    ])
    <p class="small text-muted mt-n3 mb-4">
        Modèle : lissage exponentiel de Holt (niveau + tendance), paramètres appris sur l'historique
        (α = {{ $prevision['metriques']['alpha'] }}, β = {{ $prevision['metriques']['beta'] }}).
        Erreur moyenne des prévisions à un jour : {{ number_format($prevision['metriques']['mae'], 1) }} kWh
        ({{ number_format($prevision['metriques']['mape'], 0) }} %). Le modèle ne connaît pas la météo :
        les jours nuageux restent imprévisibles, d'où l'intervalle large.
    </p>
@else
    <div class="alert alert-light border">Il faut au moins {{ config('ia.prevision.minimum_jours') }} jours de production récents pour afficher la courbe et la prévision.</div>
@endif

{{-- Membre B — IA : offre suggérée --}}
@if($suggestion)
    <div class="card card-body mb-4 border-success">
        <h5>💡 Offre suggérée par l'IA</h5>
        <p class="mb-2">Mettre en vente <strong>{{ number_format($suggestion['quantite_kwh'], 1) }} kWh</strong>
            à <strong>{{ number_format($suggestion['prix_kwh'], 4) }} DT/kWh</strong>.</p>
        <ul class="small mb-3">
            @foreach($suggestion['raisons'] as $r)<li>{{ $r }}</li>@endforeach
        </ul>
        @if($suggestion['quantite_kwh'] > 0)
            <a class="btn btn-success align-self-start" href="{{ route('offres.create', [
                'installation_id' => $installation->id,
                'quantite_kwh' => $suggestion['quantite_kwh'],
                'prix_kwh' => $suggestion['prix_kwh'],
            ]) }}">Créer cette offre</a>
        @endif
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mt-4 mb-2">
    <h4 class="mb-0">Productions ({{ number_format($totalProduit, 2) }} kWh au total)</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('productions.export', ['installation_id' => $installation->id]) }}" class="btn btn-sm btn-outline-secondary">Exporter en CSV</a>
        @can('update', $installation)
            <a href="{{ route('productions.create', ['installation_id' => $installation->id]) }}" class="btn btn-sm btn-success">+ Production</a>
        @endcan
    </div>
</div>

{{-- Membre B — fonctionnalité avancée : import CSV --}}
@can('update', $installation)
    <form action="{{ route('installations.productions.import', $installation) }}" method="POST" enctype="multipart/form-data"
          class="card card-body mb-3">
        <label class="form-label" for="fichier">Importer des productions (CSV)</label>
        <div class="row g-2">
            <div class="col-md-6">
                @csrf
                <input type="file" id="fichier" name="fichier" accept=".csv,text/csv" required
                       class="form-control @error('fichier') is-invalid @enderror">
                @error('fichier')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-auto"><button class="btn btn-outline-success">Importer</button></div>
        </div>
        <div class="form-text">Colonnes : <code>date;energie_produite_kwh</code> (ex. <code>2026-10-01;54,2</code>). Une date déjà saisie est mise à jour.</div>
        @if(session('erreurs_import'))
            <ul class="small text-danger mt-2 mb-0">@foreach(session('erreurs_import') as $e)<li>{{ $e }}</li>@endforeach</ul>
        @endif
    </form>
@endcan

<table class="table table-sm table-striped bg-white">
    <thead><tr><th>Date</th><th>kWh</th><th></th></tr></thead>
    <tbody>
    @forelse($dernieresProductions as $p)
        <tr>
            <td>{{ $p->date->format('d/m/Y') }}</td>
            <td>{{ number_format($p->energie_produite_kwh, 2) }}</td>
            <td><a href="{{ route('productions.show', $p) }}">Voir</a></td>
        </tr>
    @empty
        <tr><td colspan="3" class="text-center">Aucune production.</td></tr>
    @endforelse
    </tbody>
</table>
<a href="{{ route('productions.index', ['installation_id' => $installation->id]) }}">Toutes les productions de cette installation</a>

<div class="d-flex justify-content-between mt-4 mb-2">
    <h4>Offres</h4>
    @can('update', $installation)
        <a href="{{ route('offres.create', ['installation_id' => $installation->id]) }}" class="btn btn-sm btn-success">+ Offre</a>
    @endcan
</div>
<table class="table table-sm table-striped bg-white">
    <thead><tr><th>#</th><th>kWh</th><th>Prix/kWh</th><th>Période</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($installation->offres as $o)
        <tr>
            <td>{{ $o->id }}</td>
            <td>{{ number_format($o->quantite_kwh, 2) }}</td>
            <td>{{ number_format($o->prix_kwh, 4) }}</td>
            <td>{{ $o->date_debut->format('d/m/Y') }} → {{ $o->date_fin->format('d/m/Y') }}</td>
            <td>{{ $o->statut }}</td>
            <td><a href="{{ route('offres.show', $o) }}">Voir</a></td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center">Aucune offre.</td></tr>
    @endforelse
    </tbody>
</table>
<a href="{{ route('installations.index') }}" class="btn btn-secondary mt-3">Retour</a>
@endsection
