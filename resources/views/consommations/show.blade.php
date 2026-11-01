@extends('layouts.app')
@section('title', 'Détail relevé')
@section('content')
<h2>Relevé du {{ $consommation->date->format('d/m/Y') }}</h2>
<div class="card card-body">
    <p><strong>Compteur :</strong> <a href="{{ route('compteurs.show', $consommation->compteur) }}">{{ $consommation->compteur->reference }}</a></p>
    <p><strong>Propriétaire :</strong> {{ $consommation->compteur->proprietaire->name }}</p>
    <p class="mb-0"><strong>Énergie consommée :</strong> {{ number_format($consommation->energie_consommee_kwh, 2) }} kWh</p>
</div>
<a href="{{ route('consommations.index') }}" class="btn btn-secondary mt-3">Retour</a>
@endsection
