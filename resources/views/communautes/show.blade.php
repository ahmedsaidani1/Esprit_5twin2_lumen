@extends('layouts.app')
@section('title', 'Détail communauté')
@section('content')
<h2>{{ $communaute->nom }}</h2>
<div class="card card-body">
    <p><strong>Ville :</strong> {{ $communaute->ville ?? '—' }}</p>
    <p class="mb-0"><strong>Échanges :</strong>
        <a href="{{ route('echanges.index', ['communaute_id' => $communaute->id]) }}">{{ $communaute->echanges_count }}</a></p>
</div>

<div class="d-flex justify-content-between mt-4 mb-2">
    <h4>Installations</h4>
    <a href="{{ route('installations.create') }}" class="btn btn-sm btn-success">+ Installation</a>
</div>
<table class="table table-sm table-striped bg-white">
    <thead><tr><th>Nom</th><th>Propriétaire</th><th>Type</th><th>kW</th><th></th></tr></thead>
    <tbody>
    @forelse($communaute->installations as $i)
        <tr>
            <td>{{ $i->nom }}</td>
            <td>{{ $i->proprietaire->name }}</td>
            <td>{{ ucfirst($i->type) }}</td>
            <td>{{ number_format($i->puissance_kw, 2) }}</td>
            <td><a href="{{ route('installations.show', $i) }}">Voir</a></td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-center">Aucune installation.</td></tr>
    @endforelse
    </tbody>
</table>
<a href="{{ route('communautes.index') }}" class="btn btn-secondary mt-3">Retour</a>
@endsection
