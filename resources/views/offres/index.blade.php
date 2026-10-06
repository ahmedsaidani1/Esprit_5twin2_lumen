@extends('layouts.app')
@section('title', 'Offres')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Liste des offres</h2>
    <a href="{{ route('offres.create') }}" class="btn btn-success">+ Nouvelle offre</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="statut" class="form-select">
            <option value="">Tous les statuts</option>
            @foreach(\App\Models\Offre::STATUTS as $s)
                <option value="{{ $s }}" @selected(request('statut') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="installation_id" class="form-select">
            <option value="">Toutes les installations</option>
            @foreach($installations as $i)
                <option value="{{ $i->id }}" @selected(request('installation_id') == $i->id)>{{ $i->nom }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-success">Filtrer</button>
        <a href="{{ route('offres.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<table class="table table-striped bg-white">
    <thead>
        <tr><th>#</th><th>Installation</th><th>Quantité (kWh)</th><th>Prix/kWh</th>
            <th>Début</th><th>Fin</th><th>Statut</th><th>Actions</th></tr>
    </thead>
    <tbody>
    @forelse($offres as $o)
        <tr>
            <td>{{ $o->id }}</td>
            <td>{{ $o->installation->nom }}</td>
            <td>{{ number_format($o->quantite_kwh, 2) }}</td>
            <td>{{ number_format($o->prix_kwh, 4) }}</td>
            <td>{{ $o->date_debut->format('d/m/Y') }}</td>
            <td>{{ $o->date_fin->format('d/m/Y') }}</td>
            <td>
                <span class="badge bg-{{ $o->statut == 'disponible' ? 'success' : ($o->statut == 'reservee' ? 'warning' : 'secondary') }}">
                    {{ $o->statut }}
                </span>
            </td>
            <td class="text-nowrap">
                <a href="{{ route('offres.show', $o) }}" class="btn btn-sm btn-info">Voir</a>
                <a href="{{ route('offres.edit', $o) }}" class="btn btn-sm btn-warning">Modifier</a>
                <form action="{{ route('offres.destroy', $o) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer cette offre ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center">Aucune offre.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $offres->links('pagination::bootstrap-5') }}
@endsection
