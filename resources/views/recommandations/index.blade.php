@extends('layouts.app')
@section('title', 'Recommandations IA')
@section('content')
<div class="mb-4">
    <h2>Matching intelligent</h2>
    <p class="text-muted">Décris ton besoin : les candidats sont classés à partir des échanges acceptés ou terminés dans la même communauté.</p>
</div>

<form action="{{ route('recommandations.recommend') }}" method="POST" class="card card-body mb-4">
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <label for="role" class="form-label">Je suis</label>
            <select id="role" name="role" class="form-select" required>
                <option value="acheteur" @selected(old('role', $criteria['role'] ?? '') === 'acheteur')>Acheteur — je cherche de l’énergie</option>
                <option value="vendeur" @selected(old('role', $criteria['role'] ?? '') === 'vendeur')>Vendeur — je propose de l’énergie</option>
            </select>
        </div>
        <div class="col-md-6">
            <label for="communaute_id" class="form-label">Communauté</label>
            <select id="communaute_id" name="communaute_id" class="form-select" required>
                <option value="">Choisir une communauté</option>
                @foreach($communautes as $communaute)
                    <option value="{{ $communaute->id }}" @selected((string) old('communaute_id', $criteria['communaute_id'] ?? '') === (string) $communaute->id)>
                        {{ $communaute->nom }}{{ $communaute->ville ? ' — '.$communaute->ville : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label for="quantite_kwh" class="form-label">Quantité souhaitée (kWh)</label>
            <input id="quantite_kwh" name="quantite_kwh" type="number" min="0.01" step="0.01" class="form-control" value="{{ old('quantite_kwh', $criteria['quantite_kwh'] ?? '') }}" required>
        </div>
        <div class="col-md-6">
            <label for="prix_unitaire" class="form-label">Prix cible par kWh</label>
            <input id="prix_unitaire" name="prix_unitaire" type="number" min="0.0001" step="0.0001" class="form-control" value="{{ old('prix_unitaire', $criteria['prix_unitaire'] ?? '') }}" required>
        </div>
    </div>
    <div class="mt-3">
        <button class="btn btn-success">Trouver des partenaires</button>
    </div>
</form>

@if($recommendations !== null)
    <h3 class="h5 mb-3">Partenaires recommandés</h3>
    @if($recommendations->isEmpty())
        <div class="alert alert-info">Aucun historique exploitable dans cette communauté. Les recommandations apparaîtront après des échanges acceptés ou terminés.</div>
    @else
        <div class="table-responsive">
            <table class="table table-striped bg-white align-middle">
                <thead>
                    <tr><th>Partenaire</th><th>Rôle</th><th>Compatibilité</th><th>kWh moyens</th><th>Prix moyen/kWh</th><th>Échanges terminés</th></tr>
                </thead>
                <tbody>
                    @foreach($recommendations as $recommendation)
                        <tr>
                            <td>{{ $recommendation['user']->name }}</td>
                            <td>{{ ucfirst($recommendation['role']) }}</td>
                            <td><span class="badge bg-success">{{ $recommendation['score'] }}%</span></td>
                            <td>{{ number_format($recommendation['average_quantity'], 2, ',', ' ') }}</td>
                            <td>{{ number_format($recommendation['average_price'], 4, ',', ' ') }}</td>
                            <td>{{ $recommendation['completed_count'] }} / {{ $recommendation['transaction_count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="small text-muted">Le score combine la proximité du prix et de la quantité, l’expérience dans la communauté et le taux d’échanges terminés.</p>
    @endif
@endif
@endsection
