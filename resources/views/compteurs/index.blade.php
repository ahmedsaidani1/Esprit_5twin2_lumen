@extends('layouts.app')
@section('title', 'Compteurs')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Mes compteurs</h2>
    @can('create', \App\Models\Compteur::class)
    <a href="{{ route('compteurs.create') }}" class="btn btn-success">+ Nouveau compteur</a>
    @endcan
</div>
<form method="GET" class="row g-2 mb-3" data-autofilter>
    <div class="col-md-3"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher une référence"></div>
    <div class="col-md-3">
        <select name="statut" class="form-select">
            <option value="">Tous les statuts</option>
            @foreach(\App\Models\Compteur::STATUTS as $s)
                <option value="{{ $s }}" @selected(request('statut') == $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
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
        <a href="{{ route('compteurs.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<div id="liste">
<table class="table table-striped bg-white">
    <thead><tr><th>Référence</th><th>Propriétaire</th><th>Installation</th><th>Installé le</th><th>Statut</th><th>Relevés</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($compteurs as $c)
        <tr>
            <td>{{ $c->reference }}</td>
            <td>{{ $c->proprietaire->name }}</td>
            <td>{{ $c->installation->nom ?? '—' }}</td>
            <td>{{ $c->date_installation->format('d/m/Y') }}</td>
            <td><span class="badge bg-{{ $c->statut === 'actif' ? 'success' : ($c->statut === 'en_panne' ? 'danger' : 'secondary') }}">{{ str_replace('_', ' ', $c->statut) }}</span></td>
            <td>{{ $c->consommations_count }}</td>
            <td class="text-nowrap">
                <a href="{{ route('compteurs.show', $c) }}" class="btn btn-sm btn-info">Voir</a>
                @can('update', $c)
                <a href="{{ route('compteurs.edit', $c) }}" class="btn btn-sm btn-warning">Modifier</a>
                @endcan
                @can('delete', $c)
                <form action="{{ route('compteurs.destroy', $c) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer ce compteur ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center">Aucun compteur.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $compteurs->links('pagination::bootstrap-5') }}
</div>
@push('scripts')
@include('partials.autofilter')
@endpush
@endsection
