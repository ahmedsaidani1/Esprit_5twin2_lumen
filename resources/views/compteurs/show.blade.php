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



<h4>Derniers relevés</h4>
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
