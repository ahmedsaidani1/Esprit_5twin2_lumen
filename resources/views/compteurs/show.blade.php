@extends('layouts.app')
@section('title', 'Compteur ' . $compteur->reference)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Compteur {{ $compteur->reference }}</h2>
    <div class="d-flex gap-2">
        @if($compteur->statut === 'actif')
            <a href="{{ route('consommations.create', ['compteur_id' => $compteur->id]) }}" class="btn btn-success">+ Relevé</a>
        @endif
        @can('update', $compteur)
            <a href="{{ route('compteurs.edit', $compteur) }}" class="btn btn-outline-secondary">Modifier</a>
        @endcan
    </div>
</div>
<div class="card card-body mb-4">
    <div class="row">
        <div class="col-md-3"><strong>Propriétaire :</strong> {{ $compteur->proprietaire->name }}</div>
        <div class="col-md-3"><strong>Installation :</strong>
            @if($compteur->installation)
                <a href="{{ route('installations.show', $compteur->installation) }}">{{ $compteur->installation->nom }}</a>
            @else — @endif
        </div>
        <div class="col-md-3"><strong>Installé le :</strong> {{ $compteur->date_installation->format('d/m/Y') }}</div>
        <div class="col-md-3"><strong>Statut :</strong> {{ str_replace('_', ' ', $compteur->statut) }}</div>
    </div>
</div>

{{-- Membre C — IA : courbe, anomalies et prévision --}}
@if(count($serie) >= 7)
    @include('partials.graphique-serie', [
        'id' => 'graphique-consommation',
        'titre' => 'Consommation des derniers jours' . ($prevision ? ' et prévision IA sur ' . count($prevision['prevision']) . ' jours' : ''),
        'libelle' => 'Consommation',
        'historique' => $serie,
        'prevision' => $prevision['prevision'] ?? [],
        'anomalies' => $anomalies,
    ])

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card card-body h-100">
                <h5>Anomalies détectées par l'IA</h5>
                @forelse($anomalies as $a)
                    <p class="mb-1">
                        <span class="badge bg-danger">⚠ {{ $a['sens'] === 'pic' ? 'Pic' : 'Chute' }}</span>
                        {{ \Carbon\Carbon::parse($a['date'])->format('d/m/Y') }} : {{ number_format($a['kwh'], 2) }} kWh
                        <span class="text-muted small">(score {{ $a['score'] }})</span>
                    </p>
                @empty
                    <p class="text-success mb-0">✓ Aucune anomalie : la consommation reste dans sa plage habituelle.</p>
                @endforelse
                <p class="small text-muted mt-2 mb-0">Méthode : score z robuste (médiane et MAD) après correction de l'effet
                    du jour de la semaine ; une valeur est anormale au-delà de |z| > {{ config('ia.consommation.seuil_anomalie') }}.</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-body h-100">
                <h5>Prévision sur {{ config('ia.consommation.horizon_jours') }} jours</h5>
                @if($prevision)
                    <p class="display-6 mb-1">{{ number_format($prevision['total'], 0, ',', ' ') }} kWh</p>
                    <p class="mb-1">soit environ {{ number_format($prevision['total'] * config('ia.tarif_reseau_kwh'), 2, ',', ' ') }} DT au tarif du réseau.</p>
                    <p class="small text-muted mb-0">Régression linéaire (tendance + effet de chaque jour de la semaine), R² = {{ $prevision['r2'] }}.</p>
                @else
                    <p class="mb-0">Il faut au moins 14 relevés pour prévoir la consommation.</p>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="alert alert-light border">Ajoutez au moins 7 relevés pour afficher la courbe et l'analyse IA.</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="mb-0">Derniers relevés</h4>
    <a href="{{ route('consommations.export', ['compteur_id' => $compteur->id]) }}" class="btn btn-sm btn-outline-secondary">Exporter en CSV</a>
</div>

{{-- Membre C — fonctionnalité avancée : import CSV --}}
@can('update', $compteur)
    <form action="{{ route('compteurs.consommations.import', $compteur) }}" method="POST" enctype="multipart/form-data"
          class="card card-body mb-3">
        @csrf
        <label class="form-label" for="fichier">Importer des relevés (CSV)</label>
        <div class="row g-2">
            <div class="col-md-6">
                <input type="file" id="fichier" name="fichier" accept=".csv,text/csv" required
                       class="form-control @error('fichier') is-invalid @enderror">
                @error('fichier')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-auto"><button class="btn btn-outline-success">Importer</button></div>
        </div>
        <div class="form-text">Colonnes : <code>date;energie_consommee_kwh</code>. Mêmes règles que la saisie : compteur actif, un relevé par jour.</div>
        @if(session('erreurs_import'))
            <ul class="small text-danger mt-2 mb-0">@foreach(session('erreurs_import') as $e)<li>{{ $e }}</li>@endforeach</ul>
        @endif
    </form>
@endcan

<table class="table table-sm table-striped bg-white">
    <thead><tr><th>Date</th><th>kWh consommés</th><th></th></tr></thead>
    <tbody>
    @forelse($releves as $r)
        <tr>
            <td>{{ $r->date->format('d/m/Y') }}</td>
            <td>{{ number_format($r->energie_consommee_kwh, 2) }}</td>
            <td><a href="{{ route('consommations.show', $r) }}">Voir</a></td>
        </tr>
    @empty
        <tr><td colspan="3" class="text-center">Aucun relevé.</td></tr>
    @endforelse
    </tbody>
</table>
<a href="{{ route('consommations.index', ['compteur_id' => $compteur->id]) }}">Tous les relevés de ce compteur</a>
<div class="mt-3"><a href="{{ route('compteurs.index') }}" class="btn btn-secondary">Retour</a></div>
@endsection
