@extends('layouts.app')
@section('title', 'Installations')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Liste des installations</h2>
    <a href="{{ route('installations.create') }}" class="btn btn-success">+ Nouvelle installation</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher par nom"></div>
    <div class="col-md-3">
        <select name="type" class="form-select">
            <option value="">Tous les types</option>
            @foreach(\App\Models\Installation::TYPES as $t)
                <option value="{{ $t }}" @selected(request('type') == $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-success">Filtrer</button>
        <a href="{{ route('installations.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<table class="table table-striped bg-white">
    <thead>
        <tr><th>#</th><th>Nom</th><th>Propriétaire</th><th>Type</th><th>Puissance (kW)</th>
            <th>Localisation</th><th>Actions</th></tr>
    </thead>
    <tbody>
    @forelse($installations as $i)
        <tr>
            <td>{{ $i->id }}</td>
            <td>{{ $i->nom }}</td>
            <td>{{ $i->proprietaire->name }}</td>
            <td>{{ ucfirst($i->type) }}</td>
            <td>{{ number_format($i->puissance_kw, 2) }}</td>
            <td>{{ $i->localisation ?? '—' }}</td>
            <td class="text-nowrap">
                <a href="{{ route('installations.show', $i) }}" class="btn btn-sm btn-info">Voir</a>
                @can('update', $i)
                <a href="{{ route('installations.edit', $i) }}" class="btn btn-sm btn-warning">Modifier</a>
                @endcan
                @can('delete', $i)
                <form action="{{ route('installations.destroy', $i) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer cette installation ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center">Aucune installation.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $installations->links('pagination::bootstrap-5') }}
@endsection
