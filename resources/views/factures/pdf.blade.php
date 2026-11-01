<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ sprintf('%05d', $facture->id) }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .entete { border-bottom: 3px solid #198754; padding-bottom: 10px; margin-bottom: 20px; }
        .marque { font-size: 22px; color: #198754; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        .parties td { vertical-align: top; width: 50%; padding: 8px 0; }
        .lignes th { background: #198754; color: #fff; text-align: left; padding: 6px; }
        .lignes td { border-bottom: 1px solid #ddd; padding: 6px; }
        .droite { text-align: right; }
        .total td { font-weight: bold; font-size: 14px; border-top: 2px solid #198754; padding-top: 8px; }
        .statut { display: inline-block; padding: 3px 8px; border-radius: 4px; color: #fff; }
        .payee { background: #198754; } .impayee { background: #e0a800; color: #222; } .annulee { background: #6c757d; }
        .pied { margin-top: 40px; font-size: 10px; color: #777; text-align: center; }
    </style>
</head>
<body>
@php $e = $facture->echange; @endphp
<div class="entete">
    <table><tr>
        <td><span class="marque">☀ SolarShare</span><br>Partage d'énergie entre voisins</td>
        <td class="droite">
            <strong>FACTURE N° {{ sprintf('%05d', $facture->id) }}</strong><br>
            Émise le {{ $facture->date_emission->format('d/m/Y') }}<br>
            Échéance : {{ $facture->date_echeance->format('d/m/Y') }}<br>
            <span class="statut {{ $facture->statut }}">{{ $facture->statut }}</span>
        </td>
    </tr></table>
</div>

<table class="parties"><tr>
    <td><strong>Vendeur</strong><br>{{ $e->vendeur->name }}<br>{{ $e->vendeur->email }}</td>
    <td><strong>Acheteur</strong><br>{{ $e->acheteur->name }}<br>{{ $e->acheteur->email }}</td>
</tr></table>

<p>Communauté : {{ $e->communaute->nom }} — Échange n° {{ $e->id }} du {{ $e->date_echange->format('d/m/Y') }}
    @if($e->offre) — Offre n° {{ $e->offre->id }} ({{ $e->offre->installation->nom }}) @endif</p>

<table class="lignes">
    <thead><tr><th>Désignation</th><th class="droite">Quantité</th><th class="droite">Prix unitaire</th><th class="droite">Montant</th></tr></thead>
    <tbody>
        <tr>
            <td>Énergie renouvelable</td>
            <td class="droite">{{ number_format($e->quantite_kwh, 2, ',', ' ') }} kWh</td>
            <td class="droite">{{ number_format($e->prix_unitaire, 4, ',', ' ') }} DT/kWh</td>
            <td class="droite">{{ number_format($e->montant, 2, ',', ' ') }} DT</td>
        </tr>
    </tbody>
    <tfoot>
        <tr class="total"><td colspan="3" class="droite">Total à payer</td><td class="droite">{{ number_format($facture->montant_total, 2, ',', ' ') }} DT</td></tr>
    </tfoot>
</table>

<p class="pied">Document généré par SolarShare le {{ now()->format('d/m/Y à H:i') }}.</p>
</body>
</html>
