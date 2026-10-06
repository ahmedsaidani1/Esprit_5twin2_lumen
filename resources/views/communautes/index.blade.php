@extends('layouts.app')
@section('title', 'Communautés')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Liste des communautés</h2>
    @can('create', \App\Models\Communaute::class)
    <a href="{{ route('communautes.create') }}" class="btn btn-success">+ Nouvelle communauté</a>
    @endcan
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher (nom, ville)"></div>
    <div class="col-auto">
        <button class="btn btn-outline-success">Filtrer</button>
        <a href="{{ route('communautes.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<table class="table table-striped bg-white">
    <thead><tr><th>#</th><th>Nom</th><th>Ville</th><th>Installations</th><th>Échanges</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($communautes as $c)
        <tr>
            <td>{{ $c->id }}</td>
            <td>{{ $c->nom }}</td>
            <td>{{ $c->ville ?? '—' }}</td>
            <td>{{ $c->installations_count }}</td>
            <td>{{ $c->echanges_count }}</td>
            <td class="text-nowrap">
                <a href="{{ route('communautes.show', $c) }}" class="btn btn-sm btn-info">Voir</a>
                @can('update', $c)
                <a href="{{ route('communautes.edit', $c) }}" class="btn btn-sm btn-warning">Modifier</a>
                @endcan
                @can('delete', $c)
                <form action="{{ route('communautes.destroy', $c) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer cette communauté ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center">Aucune communauté.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $communautes->links('pagination::bootstrap-5') }}
@endsection
