@extends('layouts.app')
@section('title', 'Détail offre')
@section('content')
<h2>Offre #{{ $offre->id }}</h2>
<div class="card card-body">
    <p><strong>Installation :</strong> <a href="{{ route('installations.show', $offre->installation) }}">{{ $offre->installation->nom }}</a></p>
    <p><strong>Propriétaire :</strong> {{ $offre->installation->proprietaire->name }}</p>
    <p><strong>Quantité :</strong> {{ number_format($offre->quantite_kwh, 2) }} kWh</p>
    <p><strong>Prix :</strong> {{ number_format($offre->prix_kwh, 4) }} / kWh</p>
    <p><strong>Montant total :</strong> {{ number_format($offre->montant, 2) }}</p>
    <p><strong>Période :</strong> {{ $offre->date_debut->format('d/m/Y') }} → {{ $offre->date_fin->format('d/m/Y') }}</p>
    <p class="mb-0"><strong>Statut :</strong> {{ $offre->statut }}</p>
</div>

<h4 class="mt-4">Échanges liés</h4>
<table class="table table-sm table-striped bg-white">
    <thead><tr><th>#</th><th>Acheteur</th><th>kWh</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($offre->echanges->load('acheteur') as $e)
        <tr>
            <td>{{ $e->id }}</td>
            <td>{{ $e->acheteur->name }}</td>
            <td>{{ $e->quantite_kwh }}</td>
            <td>{{ $e->statut }}</td>
            <td><a href="{{ route('echanges.show', $e) }}">Voir</a></td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-center">Aucun échange.</td></tr>
    @endforelse
    </tbody>
</table>
@if($offre->statut === 'disponible')
    <a href="{{ route('echanges.create', ['offre_id' => $offre->id]) }}" class="btn btn-success">Acheter / créer un échange</a>
@endif
<a href="{{ route('offres.index') }}" class="btn btn-secondary">Retour</a>
@endsection
