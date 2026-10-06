@extends('layouts.app')
@section('title', 'Tableau de bord')
@section('content')
<h2 class="mb-3">Tableau de bord</h2>

<div class="row g-3 mb-4">
    @foreach([
        ['Communautés', $stats['compteurs']['communautes'], 'communautes.index'],
        ['Installations', $stats['compteurs']['installations'], 'installations.index'],
        ['Productions', $stats['compteurs']['productions'], 'productions.index'],
        ['Offres', $stats['compteurs']['offres'], 'offres.index'],
        ['Échanges', $stats['compteurs']['echanges'], 'echanges.index'],
        ['Factures', $stats['compteurs']['factures'], 'factures.index'],
    ] as [$label, $n, $r])
        <div class="col-6 col-md-2">
            <a href="{{ route($r) }}" class="card card-body text-center text-decoration-none">
                <div class="fs-3 text-success">{{ $n }}</div>
                <div class="text-muted small">{{ $label }}</div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-body h-100">
            <h5>Énergie</h5>
            <table class="table table-sm mb-0">
                <tr><td>Produite (total)</td><td class="text-end">{{ number_format($stats['energie']['produite_kwh'], 2) }} kWh</td></tr>
                <tr><td>Produite (30 derniers jours)</td><td class="text-end">{{ number_format($stats['energie']['produite_30_jours_kwh'], 2) }} kWh</td></tr>
                <tr><td>Offerte et disponible</td><td class="text-end">{{ number_format($stats['energie']['offerte_disponible_kwh'], 2) }} kWh</td></tr>
                <tr><td>Échangée (acceptée / terminée)</td><td class="text-end">{{ number_format($stats['energie']['echangee_kwh'], 2) }} kWh</td></tr>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-body h-100">
            <h5>Facturation</h5>
            <table class="table table-sm mb-0">
                <tr><td><span class="badge bg-success">payée</span></td><td class="text-end">{{ number_format($stats['factures']['payee'], 2) }}</td></tr>
                <tr><td><span class="badge bg-warning">impayée</span></td><td class="text-end">{{ number_format($stats['factures']['impayee'], 2) }}</td></tr>
                <tr><td><span class="badge bg-secondary">annulée</span></td><td class="text-end">{{ number_format($stats['factures']['annulee'], 2) }}</td></tr>
            </table>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-body h-100">
            <h5>Offres par statut</h5>
            @foreach($stats['offres_par_statut'] as $s => $n)
                <a href="{{ route('offres.index', ['statut' => $s]) }}" class="d-flex justify-content-between text-decoration-none">
                    <span>{{ ucfirst($s) }}</span><strong>{{ $n }}</strong>
                </a>
            @endforeach
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-body h-100">
            <h5>Échanges par statut</h5>
            @foreach($stats['echanges_par_statut'] as $s => $n)
                <a href="{{ route('echanges.index', ['statut' => $s]) }}" class="d-flex justify-content-between text-decoration-none">
                    <span>{{ ucfirst($s) }}</span><strong>{{ $n }}</strong>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <h5>Derniers échanges</h5>
        <table class="table table-sm table-striped bg-white">
            @forelse($derniersEchanges as $e)
                <tr>
                    <td><a href="{{ route('echanges.show', $e) }}">#{{ $e->id }}</a></td>
                    <td>{{ $e->vendeur->name }} → {{ $e->acheteur->name }}</td>
                    <td>{{ $e->quantite_kwh }} kWh</td>
                    <td>{{ $e->statut }}</td>
                </tr>
            @empty
                <tr><td class="text-center">Aucun échange.</td></tr>
            @endforelse
        </table>
    </div>
    <div class="col-md-6">
        <h5>Offres disponibles</h5>
        <table class="table table-sm table-striped bg-white">
            @forelse($offresOuvertes as $o)
                <tr>
                    <td><a href="{{ route('offres.show', $o) }}">#{{ $o->id }}</a></td>
                    <td>{{ $o->installation->nom }}</td>
                    <td>{{ $o->quantite_kwh }} kWh à {{ $o->prix_kwh }}</td>
                    <td>jusqu'au {{ $o->date_fin->format('d/m/Y') }}</td>
                </tr>
            @empty
                <tr><td class="text-center">Aucune offre disponible.</td></tr>
            @endforelse
        </table>
    </div>
</div>
@endsection
