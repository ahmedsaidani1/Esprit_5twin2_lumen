@extends('layouts.app')
@section('title', 'Détail utilisateur')
@section('content')
<h2>{{ $user->name }}</h2>
<div class="card card-body">
    <p><strong>E-mail :</strong> {{ $user->email }}</p>
    <p><strong>Rôle :</strong> {{ $user->role }}</p>
    <p><strong>Communauté :</strong> {{ $user->communaute->nom ?? '—' }}</p>
    <p><strong>Échanges :</strong> {{ $user->echanges_vendus_count }} en tant que vendeur,
       {{ $user->echanges_achetes_count }} en tant qu'acheteur</p>
</div>

<h4 class="mt-4">Installations</h4>
<table class="table table-sm table-striped bg-white">
    <thead><tr><th>Nom</th><th>Type</th><th>kW</th><th></th></tr></thead>
    <tbody>
    @forelse($user->installations as $i)
        <tr>
            <td>{{ $i->nom }}</td>
            <td>{{ ucfirst($i->type) }}</td>
            <td>{{ number_format($i->puissance_kw, 2) }}</td>
            <td><a href="{{ route('installations.show', $i) }}">Voir</a></td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-center">Aucune installation.</td></tr>
    @endforelse
    </tbody>
</table>
<a href="{{ route('users.index') }}" class="btn btn-secondary mt-3">Retour</a>
@endsection
