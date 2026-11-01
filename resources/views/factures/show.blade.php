@extends('layouts.app')
@section('title', 'Détail facture')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Facture n° {{ sprintf('%05d', $facture->id) }}</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('factures.pdf', $facture) }}" class="btn btn-outline-secondary" target="_blank">Télécharger le PDF</a>
        @can('update', $facture)
            <form action="{{ route('factures.envoyer', $facture) }}" method="POST">
                @csrf
                <button class="btn btn-outline-success">Envoyer par e-mail à l'acheteur</button>
            </form>
            <a href="{{ route('factures.edit', $facture) }}" class="btn btn-outline-secondary">Modifier</a>
        @endcan
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card card-body h-100">
            <p><strong>Échange :</strong> <a href="{{ route('echanges.show', $facture->echange) }}">n° {{ $facture->echange_id }}</a></p>
            <p><strong>Vendeur :</strong> {{ $facture->echange->vendeur->name }}</p>
            <p><strong>Acheteur :</strong> {{ $facture->echange->acheteur->name }}</p>
            <p><strong>Communauté :</strong> {{ $facture->echange->communaute->nom }}</p>
            <p><strong>Montant total :</strong> {{ number_format($facture->montant_total, 2, ',', ' ') }} DT</p>
            <p><strong>Émission :</strong> {{ $facture->date_emission->format('d/m/Y') }}</p>
            <p><strong>Échéance :</strong> {{ $facture->date_echeance->format('d/m/Y') }}
                @if($facture->statut === 'impayee' && $facture->date_echeance->isPast())
                    <span class="badge bg-danger">en retard</span>
                @endif
            </p>
            <p class="mb-0"><strong>Statut :</strong> {{ $facture->statut }}</p>
        </div>
    </div>

    {{-- Membre A — IA : risque d'impayé (visible du vendeur et de l'admin) --}}
    @if($risque)
        @php $couleur = ['faible' => 'success', 'moyen' => 'warning', 'élevé' => 'danger'][$risque['niveau']]; @endphp
        <div class="col-md-5">
            <div class="card card-body h-100 border-{{ $couleur }}">
                <h5>Risque d'impayé (IA)</h5>
                <p class="display-6 mb-0">{{ round($risque['probabilite'] * 100) }} %</p>
                <p><span class="badge bg-{{ $couleur }}">risque {{ $risque['niveau'] }}</span></p>
                <p class="small mb-1">Principaux facteurs :</p>
                <ul class="small">
                    @foreach($risque['facteurs'] as $f)
                        <li>{{ $f['libelle'] }} : {{ $f['effet'] > 0 ? 'augmente' : 'réduit' }} le risque</li>
                    @endforeach
                </ul>
                <p class="small text-muted mb-0">
                    Régression logistique entraînée sur {{ $modele['exemples']['total'] }} factures
                    (exactitude {{ round($modele['metriques']['exactitude'] * 100) }} %, AUC {{ number_format($modele['metriques']['auc'], 2) }}
                    sur les données de test).
                </p>
            </div>
        </div>
    @endif
</div>
<a href="{{ route('factures.index') }}" class="btn btn-secondary mt-3">Retour</a>
@endsection
