@extends('layouts.app')
@section('title', 'Détail installation')
@section('content')
<h2>{{ $installation->nom }}</h2>
<div class="card card-body">
    <p><strong>Propriétaire :</strong> {{ $installation->proprietaire->name }}</p>
    <p><strong>Communauté :</strong> {{ $installation->communaute->nom ?? '—' }}</p>
    <p><strong>Type :</strong> {{ ucfirst($installation->type) }}</p>
    <p><strong>Puissance :</strong> {{ number_format($installation->puissance_kw, 2) }} kW</p>
    <p class="mb-0"><strong>Localisation :</strong> {{ $installation->localisation ?? '—' }}</p>
</div>

<div class="d-flex justify-content-between mt-4 mb-2">
    <h4>Productions ({{ number_format($installation->productions->sum('energie_produite_kwh'), 2) }} kWh au total)</h4>
    <a href="{{ route('productions.create', ['installation_id' => $installation->id]) }}" class="btn btn-sm btn-success">+ Production</a>
</div>
<table class="table table-sm table-striped bg-white">
    <thead><tr><th>Date</th><th>kWh</th><th></th></tr></thead>
    <tbody>
    @forelse($installation->productions->sortByDesc('date') as $p)
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

<div class="d-flex justify-content-between mt-4 mb-2">
    <h4>Offres</h4>
    <a href="{{ route('offres.create', ['installation_id' => $installation->id]) }}" class="btn btn-sm btn-success">+ Offre</a>
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
