@extends('layouts.app')
@section('title', 'Détail facture')
@section('content')
<h2>Facture #{{ $facture->id }}</h2>
<div class="card card-body">
    <p><strong>Échange :</strong> <a href="{{ route('echanges.show', $facture->echange) }}">#{{ $facture->echange_id }}</a></p>
    <p><strong>Vendeur :</strong> {{ $facture->echange->vendeur->name }}</p>
    <p><strong>Acheteur :</strong> {{ $facture->echange->acheteur->name }}</p>
    <p><strong>Communauté :</strong> {{ $facture->echange->communaute->nom }}</p>
    <p><strong>Montant total :</strong> {{ number_format($facture->montant_total, 2) }}</p>
    <p><strong>Émission :</strong> {{ $facture->date_emission->format('d/m/Y') }}</p>
    <p><strong>Échéance :</strong> {{ $facture->date_echeance->format('d/m/Y') }}</p>
    <p><strong>Statut :</strong> {{ $facture->statut }}</p>
</div>
<a href="{{ route('factures.index') }}" class="btn btn-secondary mt-3">Retour</a>
@endsection