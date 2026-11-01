@extends('layouts.app')
@section('title', 'Productions')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Liste des productions</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('productions.export', request()->query()) }}" class="btn btn-outline-secondary">Exporter en CSV</a>
        <a href="{{ route('productions.create') }}" class="btn btn-success">+ Nouvelle production</a>
    </div>
</div>
<form method="GET" class="row g-2 mb-3" data-autofilter>
    <div class="col-md-3">
        <select name="installation_id" class="form-select">
            <option value="">Toutes les installations</option>
            @foreach($installations as $i)
                <option value="{{ $i->id }}" @selected(request('installation_id') == $i->id)>{{ $i->nom }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="du" value="{{ request('du') }}" class="form-control" title="Du"></div>
    <div class="col-md-2"><input type="date" name="au" value="{{ request('au') }}" class="form-control" title="Au"></div>
    <div class="col-auto">
        <button class="btn btn-outline-success">Filtrer</button>
        <a href="{{ route('productions.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<div id="liste">
<table class="table table-striped bg-white">
    <thead>
        <tr><th>#</th><th>Installation</th><th>Date</th><th>Énergie produite (kWh)</th><th>Actions</th></tr>
    </thead>
    <tbody>
    @forelse($productions as $p)
        <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->installation->nom }}</td>
            <td>{{ $p->date->format('d/m/Y') }}</td>
            <td>{{ number_format($p->energie_produite_kwh, 2) }}</td>
            <td class="text-nowrap">
                <a href="{{ route('productions.show', $p) }}" class="btn btn-sm btn-info">Voir</a>
                @can('update', $p)
                <a href="{{ route('productions.edit', $p) }}" class="btn btn-sm btn-warning">Modifier</a>
                @endcan
                @can('delete', $p)
                <form action="{{ route('productions.destroy', $p) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer cette production ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="text-center">Aucune production.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $productions->links('pagination::bootstrap-5') }}
</div>
@push('scripts')
@include('partials.autofilter')
@endpush
@endsection
