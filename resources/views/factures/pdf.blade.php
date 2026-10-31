<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #{{ $facture->id }}</title>
    <style>
        @page { margin: 36px 42px; }
        body { font-family: DejaVu Sans, sans-serif; color: #24312b; font-size: 12px; }
        .top { border-bottom: 3px solid #198754; padding-bottom: 18px; margin-bottom: 28px; }
        .brand { color: #198754; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        h1 { font-size: 28px; margin: 8px 0 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 22px; }
        th { background: #edf5ef; color: #245c3b; text-align: left; }
        th, td { padding: 10px 9px; border-bottom: 1px solid #dfe7e1; }
        .right { text-align: right; }
        .panel { width: 48%; display: inline-block; vertical-align: top; }
        .panel p { margin: 6px 0; }
        .total { margin-top: 18px; text-align: right; font-size: 18px; font-weight: bold; color: #198754; }
        .footer { margin-top: 48px; border-top: 1px solid #dfe7e1; padding-top: 12px; color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    <div class="top">
        <div class="brand">SolarShare</div>
        <h1>Facture #{{ $facture->id }}</h1>
        <div class="muted">Émise le {{ $facture->date_emission->format('d/m/Y') }}</div>
    </div>

    <div class="panel">
        <strong>Vendeur</strong>
        <p>{{ $facture->echange->vendeur->name }}</p>
    </div>
    <div class="panel">
        <strong>Acheteur</strong>
        <p>{{ $facture->echange->acheteur->name }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Quantité</th>
                <th class="right">Prix unitaire</th>
                <th class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    Échange d’énergie #{{ $facture->echange_id }}<br>
                    <span class="muted">{{ $facture->echange->communaute->nom }} · {{ $facture->echange->date_echange->format('d/m/Y') }}</span>
                </td>
                <td class="right">{{ number_format($facture->echange->quantite_kwh, 2, ',', ' ') }} kWh</td>
                <td class="right">{{ number_format($facture->echange->prix_unitaire, 4, ',', ' ') }}</td>
                <td class="right">{{ number_format($facture->montant_total, 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total">Total à payer : {{ number_format($facture->montant_total, 2, ',', ' ') }}</div>

    <table>
        <tbody>
            <tr><td><strong>Date d’échéance</strong></td><td>{{ $facture->date_echeance->format('d/m/Y') }}</td></tr>
            <tr><td><strong>Statut</strong></td><td>{{ ucfirst($facture->statut) }}</td></tr>
            <tr><td><strong>Communauté</strong></td><td>{{ $facture->echange->communaute->nom }}{{ $facture->echange->communaute->ville ? ' — '.$facture->echange->communaute->ville : '' }}</td></tr>
        </tbody>
    </table>

    <div class="footer">Facture générée par SolarShare.</div>
</body>
</html>
