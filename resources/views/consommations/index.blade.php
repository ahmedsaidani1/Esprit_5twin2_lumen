@extends('layouts.app')
@section('title', 'Consommations')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Relevés de consommation</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('consommations.export', request()->query()) }}" class="btn btn-outline-secondary">Exporter en CSV</a>
        @can('create', \App\Models\Consommation::class)
        <a href="{{ route('consommations.create') }}" class="btn btn-success">+ Nouveau relevé</a>
        @endcan
    </div>
</div>
<form method="GET" class="row g-2 mb-3" data-autofilter>
    <div class="col-md-3">
        <select name="compteur_id" class="form-select">
            <option value="">Tous les compteurs</option>
            @foreach($compteurs as $c)
                <option value="{{ $c->id }}" @selected(request('compteur_id') == $c->id)>{{ $c->reference }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="du" value="{{ request('du') }}" class="form-control" title="Du"></div>
    <div class="col-md-2"><input type="date" name="au" value="{{ request('au') }}" class="form-control" title="Au"></div>
    <div class="col-auto">
        <button class="btn btn-outline-success">Filtrer</button>
        <a href="{{ route('consommations.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<div id="liste">
<table class="table table-striped bg-white">
    <thead><tr><th>Date</th><th>Compteur</th><th>kWh consommés</th><th>Actions</th></tr></thead>
    <tbody>
    @forelse($consommations as $r)
        <tr>
            <td>{{ $r->date->format('d/m/Y') }}</td>
            <td>{{ $r->compteur->reference }}</td>
            <td>{{ number_format($r->energie_consommee_kwh, 2) }}</td>
            <td class="text-nowrap">
                <a href="{{ route('consommations.show', $r) }}" class="btn btn-sm btn-info">Voir</a>
                @can('update', $r)
                <a href="{{ route('consommations.edit', $r) }}" class="btn btn-sm btn-warning">Modifier</a>
                @endcan
                @can('delete', $r)
                <form action="{{ route('consommations.destroy', $r) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer ce relevé ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="text-center">Aucun relevé.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $consommations->links('pagination::bootstrap-5') }}
</div>
@push('scripts')
@include('partials.autofilter')
@endpush
@endsection
