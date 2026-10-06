@extends('layouts.app')
@section('title', 'Détail échange')
@section('content')
<h2>Échange #{{ $echange->id }}</h2>
<div class="card card-body">
    <p><strong>Vendeur :</strong> {{ $echange->vendeur->name }}</p>
    <p><strong>Acheteur :</strong> {{ $echange->acheteur->name }}</p>
    <p><strong>Communauté :</strong> {{ $echange->communaute->nom }}</p>
    <p><strong>Quantité :</strong> {{ $echange->quantite_kwh }} kWh</p>
    <p><strong>Prix unitaire :</strong> {{ $echange->prix_unitaire }}</p>
    <p><strong>Montant calculé :</strong> {{ number_format($echange->montant, 2) }}</p>
    <p><strong>Date :</strong> {{ $echange->date_echange->format('d/m/Y H:i') }}</p>
    <p><strong>Statut :</strong> {{ $echange->statut }}</p>
    <p><strong>Facture :</strong>
        @if($echange->facture)
            <a href="{{ route('factures.show', $echange->facture) }}">Facture #{{ $echange->facture->id }}</a>
        @else
            Aucune <a href="{{ route('factures.create') }}">(créer)</a>
        @endif
    </p>
</div>
<a href="{{ route('echanges.index') }}" class="btn btn-secondary mt-3">Retour</a>
@endsection