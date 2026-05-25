<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; }
        .top { width: 100%; border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 8px; }
        .school { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .muted { color: #555; }
        .title { text-align: center; font-size: 16px; font-weight: bold; text-transform: uppercase; margin: 8px 0 2px; }
        .period { text-align: center; font-size: 11px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px; vertical-align: middle; }
        th { background: #f0f0f0; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .section { margin-top: 8px; }
        .net { background: #111; color: #fff; font-size: 13px; font-weight: bold; }
        .no-border td { border: 0; padding: 3px 0; }
        .signature { margin-top: 18px; width: 100%; }
        .signature td { border: 0; width: 50%; vertical-align: top; }
    </style>
</head>
<body>
    <div class="top">
        <div class="school">{{ $ecole?->nomEcole ?? 'École' }}</div>
        <div class="muted">
            {{ $ecole?->academie ?: '' }} {{ $ecole?->cap ? ' - '.$ecole?->cap : '' }}<br>
            Bulletin N° {{ $row['reference'] }}
        </div>
    </div>

    <div class="title">Bulletin de salaire</div>
    <div class="period">{{ $months[$filters['mois']] ?? $filters['mois'] }} {{ $filters['annee'] }}</div>

    <table>
        <tr>
            <th>Enseignant</th>
            <td>{{ $enseignant->nom_prenom_enseignant }}</td>
        </tr>
        <tr>
            <th>Matricule</th>
            <td>{{ $enseignant->matricule ?: '-' }}</td>
        </tr>
        <tr>
            <th>Contrat</th>
            <td>{{ $row['contract'] }}</td>
        </tr>
        <tr>
            <th>Base</th>
            <td>{{ $row['payment_type'] === 'mensuel' ? 'Salaire mensuel' : ($row['source'] === 'presence' ? 'Cahier de présence' : 'Émargements') }}</td>
        </tr>
    </table>

    <table class="section">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="right">Base</th>
                <th class="right">Taux</th>
                <th class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @if($row['contract'] === 'VCT')
                <tr>
                    <td>Heures validées</td>
                    <td class="right">{{ number_format($row['hours'], 2, ',', ' ') }} h</td>
                    <td class="right">{{ number_format($row['hourly_rate'], 0, ',', ' ') }} F</td>
                    <td class="right">{{ number_format($row['amount_due'], 0, ',', ' ') }} F</td>
                </tr>
            @else
                <tr>
                    <td>Salaire mensuel</td>
                    <td class="right">1 mois</td>
                    <td class="right">-</td>
                    <td class="right">{{ number_format($row['amount_due'], 0, ',', ' ') }} F</td>
                </tr>
            @endif
            <tr>
                <td colspan="3" class="right"><strong>Total brut</strong></td>
                <td class="right"><strong>{{ number_format($row['amount_due'], 0, ',', ' ') }} F</strong></td>
            </tr>
            <tr>
                <td colspan="3" class="right">Déjà versé</td>
                <td class="right">{{ number_format($row['paid'], 0, ',', ' ') }} F</td>
            </tr>
            <tr class="net">
                <td colspan="3" class="right">Net à payer</td>
                <td class="right">{{ number_format($row['remaining'], 0, ',', ' ') }} F</td>
            </tr>
        </tbody>
    </table>

    <table class="section">
        <thead>
            <tr>
                <th>Date</th>
                <th class="right">Versement</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($row['salary']?->lignes ?? collect())->sortBy('date_paiement') as $line)
                <tr>
                    <td>{{ optional($line->date_paiement)->format('d/m/Y') }}</td>
                    <td class="right">{{ number_format($line->montant_verse, 0, ',', ' ') }} F</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="center muted">Aucun versement enregistré</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signature">
        <tr>
            <td>
                <strong>L'enseignant</strong><br><br><br>
                Signature
            </td>
            <td class="right">
                <strong>Administration</strong><br>
                {{ auth()->user()->nomPrenom ?? '' }}<br><br>
                Signature
            </td>
        </tr>
    </table>
</body>
</html>
