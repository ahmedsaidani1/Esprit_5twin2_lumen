@extends('layouts.app')
@section('title', 'Utilisateurs')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Liste des utilisateurs</h2>
    <a href="{{ route('users.create') }}" class="btn btn-success">+ Nouvel utilisateur</a>
</div>
<form method="GET" class="row g-2 mb-3" data-autofilter>
    <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher (nom, e-mail)"></div>
    <div class="col-md-3">
        <select name="role" class="form-select">
            <option value="">Tous les rôles</option>
            @foreach(\App\Models\User::ROLES as $r)
                <option value="{{ $r }}" @selected(request('role') == $r)>{{ ucfirst($r) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-success">Filtrer</button>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<div id="liste">
<table class="table table-striped bg-white">
    <thead><tr><th>#</th><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Communauté</th><th>Installations</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($users as $u)
        <tr>
            <td>{{ $u->id }}</td>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td><span class="badge bg-{{ $u->role === 'admin' ? 'danger' : 'secondary' }}">{{ $u->role }}</span></td>
            <td>{{ $u->communaute->nom ?? '—' }}</td>
            <td>{{ $u->installations_count }}</td>
            <td class="text-nowrap">
                <a href="{{ route('users.show', $u) }}" class="btn btn-sm btn-info">Voir</a>
                <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-warning">Modifier</a>
                @can('delete', $u)
                <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer cet utilisateur ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center">Aucun utilisateur.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $users->links('pagination::bootstrap-5') }}
</div>
@push('scripts')
@include('partials.autofilter')
@endpush
@endsection
