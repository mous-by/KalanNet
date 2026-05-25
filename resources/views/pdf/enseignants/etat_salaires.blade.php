<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #333; padding: 6px; vertical-align: middle; }
        th { background: #f2f2f2; text-align: left; }
        .title { text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 16px 0 6px; }
        .subtitle { text-align: center; font-size: 13px; font-weight: bold; margin: 0 0 12px; }
        .muted { color: #666; }
        .right { text-align: right; }
        .center { text-align: center; }
        tfoot th, tfoot td { font-weight: bold; background: #fafafa; }
    </style>
</head>
<body>
    <div style="display:flex; justify-content:space-between;">
        <div>
            <strong>MINISTERE DE L'EDUCATION NATIONALE</strong><br>
            {{ $ecole?->academie }}<br>
            {{ $ecole?->cap }}
        </div>
        <div class="right">
            <strong>REPUBLIQUE DU MALI</strong><br>
            Un Peuple - Un But - Une Foi
        </div>
    </div>

    <p class="title">{{ $ecole?->nomEcole ?? 'École' }}</p>
    <p class="subtitle">Mandat de paiement des salaires enseignants</p>
    <p class="muted">
        Mois : {{ $months[$filters['mois']] ?? $filters['mois'] }} {{ $filters['annee'] }} |
        Base : {{ $sources[$filters['source']] ?? $filters['source'] }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Nom & Prénom</th>
                <th>Contrat</th>
                <th class="right">Heures VCT</th>
                <th class="right">Prix/heure</th>
                <th class="right">Salaire du mois</th>
                <th class="right">Déjà versé</th>
                <th class="right">Reste</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['enseignant']->nom_prenom_enseignant }}</td>
                    <td>{{ $row['contract'] }}</td>
                    <td class="right">{{ $row['contract'] === 'VCT' ? number_format($row['hours'], 2, ',', ' ') : '-' }}</td>
                    <td class="right">{{ $row['contract'] === 'VCT' ? number_format($row['hourly_rate'], 0, ',', ' ') . ' F' : '-' }}</td>
                    <td class="right">{{ number_format($row['amount_due'], 0, ',', ' ') }} F</td>
                    <td class="right">{{ number_format($row['paid'], 0, ',', ' ') }} F</td>
                    <td class="right">{{ number_format($row['remaining'], 0, ',', ' ') }} F</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center muted">Aucun enseignant à afficher pour cette période.</td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="4" class="right">MONTANT TOTAL À PRÉVOIR</td>
                    <td class="right">{{ number_format($summary['due'], 0, ',', ' ') }} F</td>
                    <td class="right">{{ number_format($summary['paid'], 0, ',', ' ') }} F</td>
                    <td class="right">{{ number_format($summary['remaining'], 0, ',', ' ') }} F</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div style="margin-top:32px; text-align:right;">
        Établi par : {{ auth()->user()->nomPrenom ?? '' }}<br><br><br>
        Signature
    </div>
</body>
</html>
