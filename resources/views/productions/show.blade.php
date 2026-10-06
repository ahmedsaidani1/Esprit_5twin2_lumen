@extends('layouts.app')
@section('title', 'Détail production')
@section('content')
<h2>Production #{{ $production->id }}</h2>
<div class="card card-body">
    <p><strong>Installation :</strong> <a href="{{ route('installations.show', $production->installation) }}">{{ $production->installation->nom }}</a></p>
    <p><strong>Propriétaire :</strong> {{ $production->installation->proprietaire->name }}</p>
    <p><strong>Date :</strong> {{ $production->date->format('d/m/Y') }}</p>
    <p class="mb-0"><strong>Énergie produite :</strong> {{ number_format($production->energie_produite_kwh, 2) }} kWh</p>
</div>
<a href="{{ route('productions.index') }}" class="btn btn-secondary mt-3">Retour</a>
@endsection
