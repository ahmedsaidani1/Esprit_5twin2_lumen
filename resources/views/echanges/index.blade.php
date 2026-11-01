@extends('layouts.app')
@section('title', 'Échanges')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Liste des échanges</h2>
    <a href="{{ route('echanges.create') }}" class="btn btn-success">+ Nouvel échange</a>
</div>
<form method="GET" class="row g-2 mb-3" data-autofilter>
    <div class="col-md-3">
        <select name="statut" class="form-select">
            <option value="">Tous les statuts</option>
            @foreach(\App\Models\Echange::STATUTS as $s)
                <option value="{{ $s }}" @selected(request('statut') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <select name="communaute_id" class="form-select">
            <option value="">Toutes les communautés</option>
            @foreach($communautes as $c)
                <option value="{{ $c->id }}" @selected(request('communaute_id') == $c->id)>{{ $c->nom }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-success">Filtrer</button>
        <a href="{{ route('echanges.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<div id="liste">
<table class="table table-striped bg-white">
    <thead>
        <tr><th>#</th><th>Vendeur</th><th>Acheteur</th><th>Communauté</th>
            <th>kWh</th><th>Prix/kWh</th><th>Date</th><th>Statut</th><th>Actions</th></tr>
    </thead>
    <tbody>
    @forelse($echanges as $e)
        <tr>
            <td>{{ $e->id }}</td>
            <td>{{ $e->vendeur->name }}</td>
            <td>{{ $e->acheteur->name }}</td>
            <td>{{ $e->communaute->nom }}</td>
            <td>{{ $e->quantite_kwh }}</td>
            <td>{{ $e->prix_unitaire }}</td>
            <td>{{ $e->date_echange->format('d/m/Y H:i') }}</td>
            <td><span class="badge bg-secondary">{{ $e->statut }}</span></td>
            <td class="text-nowrap">
                <a href="{{ route('echanges.show', $e) }}" class="btn btn-sm btn-info">Voir</a>
                @can('update', $e)
                <a href="{{ route('echanges.edit', $e) }}" class="btn btn-sm btn-warning">Modifier</a>
                @endcan
                @can('delete', $e)
                <form action="{{ route('echanges.destroy', $e) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer cet échange ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="9" class="text-center">Aucun échange.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $echanges->links('pagination::bootstrap-5') }}
</div>
@push('scripts')
@include('partials.autofilter')
@endpush
@endsection