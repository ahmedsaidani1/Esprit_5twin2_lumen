@extends('layouts.app')
@section('title', 'Échanges')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Liste des échanges</h2>
    <a href="{{ route('echanges.create') }}" class="btn btn-success">+ Nouvel échange</a>
</div>
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
                <a href="{{ route('echanges.edit', $e) }}" class="btn btn-sm btn-warning">Modifier</a>
                <form action="{{ route('echanges.destroy', $e) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer cet échange ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="9" class="text-center">Aucun échange.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $echanges->links('pagination::bootstrap-5') }}
@endsection