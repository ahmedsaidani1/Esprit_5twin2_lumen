@extends('layouts.app')
@section('title', 'Factures')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Liste des factures</h2>
    <a href="{{ route('factures.create') }}" class="btn btn-success">+ Nouvelle facture</a>
</div>
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-3">
        <select name="statut" class="form-select">
            <option value="">Tous les statuts</option>
            @foreach(\App\Models\Facture::STATUTS as $s)
                <option value="{{ $s }}" @selected(request('statut') == $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-success">Filtrer</button>
        <a href="{{ route('factures.index') }}" class="btn btn-outline-secondary">Réinitialiser</a>
    </div>
</form>
<table class="table table-striped bg-white">
    <thead>
        <tr><th>#</th><th>Échange</th><th>Vendeur → Acheteur</th><th>Montant</th>
            <th>Émission</th><th>Échéance</th><th>Statut</th><th>Actions</th></tr>
    </thead>
    <tbody>
    @forelse($factures as $f)
        <tr>
            <td>{{ $f->id }}</td>
            <td>#{{ $f->echange_id }}</td>
            <td>{{ $f->echange->vendeur->name }} → {{ $f->echange->acheteur->name }}</td>
            <td>{{ number_format($f->montant_total, 2) }}</td>
            <td>{{ $f->date_emission->format('d/m/Y') }}</td>
            <td>{{ $f->date_echeance->format('d/m/Y') }}</td>
            <td>
                <span class="badge bg-{{ $f->statut == 'payee' ? 'success' : ($f->statut == 'impayee' ? 'warning' : 'secondary') }}">
                    {{ $f->statut }}
                </span>
            </td>
            <td class="text-nowrap">
                <a href="{{ route('factures.show', $f) }}" class="btn btn-sm btn-info">Voir</a>
                <a href="{{ route('factures.edit', $f) }}" class="btn btn-sm btn-warning">Modifier</a>
                <form action="{{ route('factures.destroy', $f) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Supprimer cette facture ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center">Aucune facture.</td></tr>
    @endforelse
    </tbody>
</table>
{{ $factures->links('pagination::bootstrap-5') }}
@endsection