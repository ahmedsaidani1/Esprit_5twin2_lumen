{{--
    Graphique d'une série quotidienne (kWh) : historique, prévision avec intervalle à 95 %, anomalies.
    Paramètres : $id, $titre, $libelle, $historique [date,kwh], $prevision [date,kwh,bas,haut] (optionnel),
                 $anomalies [date,kwh,sens] (optionnel).
    Couleurs validées (outil dataviz) : série #2a78d6, anomalie = statut critique #d03b3b.
    Les anomalies sont aussi listées en texte et toutes les valeurs restent lisibles dans le tableau.
--}}
@php
    $prevision = $prevision ?? [];
    $anomalies = $anomalies ?? [];
    $dates = array_merge(array_column($historique, 'date'), array_column($prevision, 'date'));
    $nbHisto = count($historique);
    $donnees = [
        'labels' => array_map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'), $dates),
        'historique' => array_merge(array_column($historique, 'kwh'), array_fill(0, count($prevision), null)),
        // la prévision repart du dernier point mesuré pour que les deux lignes se rejoignent
        'prevision' => $prevision ? array_merge(array_fill(0, max(0, $nbHisto - 1), null), [$historique[$nbHisto - 1]['kwh'] ?? null], array_column($prevision, 'kwh')) : [],
        'bas' => $prevision ? array_merge(array_fill(0, $nbHisto, null), array_column($prevision, 'bas')) : [],
        'haut' => $prevision ? array_merge(array_fill(0, $nbHisto, null), array_column($prevision, 'haut')) : [],
        'anomalies' => array_map(function ($d) use ($anomalies) {
            foreach ($anomalies as $a) {
                if ($a['date'] === $d) {
                    return $a['kwh'];
                }
            }
            return null;
        }, $dates),
        'libelle' => $libelle,
    ];
@endphp
<div class="card card-body mb-4">
    <h5 class="mb-1">{{ $titre }}</h5>
    @if($prevision)
        <p class="small text-muted mb-2">Trait plein : mesures. Pointillés : prévision ; zone claire : intervalle de confiance à 95 %.
            @if($anomalies) Points rouges : anomalies détectées. @endif</p>
    @endif
    <div style="position:relative;height:300px">
        <canvas id="{{ $id }}" role="img" aria-label="{{ $titre }}"></canvas>
    </div>
    <details class="mt-2">
        <summary class="small">Voir les données sous forme de tableau</summary>
        <div style="max-height:240px;overflow:auto">
            <table class="table table-sm mb-0">
                <thead><tr><th>Date</th><th>{{ $libelle }} (kWh)</th>@if($prevision)<th>Prévision (kWh)</th><th>Intervalle</th>@endif</tr></thead>
                <tbody>
                @foreach($historique as $p)
                    <tr><td>{{ \Carbon\Carbon::parse($p['date'])->format('d/m/Y') }}</td><td>{{ number_format($p['kwh'], 2) }}
                        @foreach($anomalies as $a) @if($a['date'] === $p['date']) <span class="badge bg-danger">anomalie : {{ $a['sens'] }}</span> @endif @endforeach</td>
                        @if($prevision)<td></td><td></td>@endif</tr>
                @endforeach
                @foreach($prevision as $p)
                    <tr class="table-light"><td>{{ \Carbon\Carbon::parse($p['date'])->format('d/m/Y') }}</td><td></td>
                        <td>{{ number_format($p['kwh'], 2) }}</td><td>{{ number_format($p['bas'], 1) }} – {{ number_format($p['haut'], 1) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </details>
</div>

@once
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
@endpush
@endonce
@push('scripts')
<script>
(() => {
    const d = @json($donnees);
    const SERIE = '#2a78d6', CRITIQUE = '#d03b3b', GRILLE = '#e7e6e2', TEXTE = '#52514e';
    const fmt = (v) => v === null || v === undefined ? null : v.toLocaleString('fr-FR', { maximumFractionDigits: 1 }) + ' kWh';

    const jeux = [{
        label: d.libelle, data: d.historique, borderColor: SERIE, backgroundColor: SERIE,
        borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.25, spanGaps: false,
    }];
    if (d.prevision.length) {
        jeux.push(
            { label: 'Intervalle haut', data: d.haut, borderWidth: 0, pointRadius: 0, fill: false, tooltip: false },
            { label: 'Intervalle 95 %', data: d.bas, borderWidth: 0, pointRadius: 0, fill: '-1', backgroundColor: 'rgba(42,120,214,0.14)' },
            { label: 'Prévision', data: d.prevision, borderColor: SERIE, borderDash: [6, 4], borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.25 },
        );
    }
    if (d.anomalies.some((v) => v !== null)) {
        jeux.push({ label: 'Anomalie', data: d.anomalies, showLine: false, pointRadius: 5, pointHoverRadius: 7,
            pointBackgroundColor: CRITIQUE, pointBorderColor: '#fcfcfb', pointBorderWidth: 2 });
    }

    new Chart(document.getElementById(@json($id)), {
        type: 'line',
        data: { labels: d.labels, datasets: jeux },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },   // infobulle sur toute la verticale
            plugins: {
                legend: { labels: { color: TEXTE, usePointStyle: true, filter: (i) => i.text !== 'Intervalle haut' } },
                tooltip: {
                    filter: (i) => i.dataset.label !== 'Intervalle haut' && i.raw !== null,
                    callbacks: { label: (c) => c.dataset.label === 'Intervalle 95 %'
                        ? `Intervalle : ${fmt(c.raw)} – ${fmt(d.haut[c.dataIndex])}`
                        : `${c.dataset.label} : ${fmt(c.raw)}` },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: TEXTE, maxTicksLimit: 12 } },
                y: { beginAtZero: true, grid: { color: GRILLE, lineWidth: 1 }, border: { display: false },
                     ticks: { color: TEXTE, callback: (v) => v + ' kWh' } },
            },
        },
    });
})();
</script>
@endpush
