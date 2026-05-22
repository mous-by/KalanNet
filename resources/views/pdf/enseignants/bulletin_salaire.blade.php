<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #333; padding: 8px; vertical-align: top; }
        th { background: #f2f2f2; text-align: left; }
        .title { text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; margin: 16px 0 6px; }
        .subtitle { text-align: center; font-size: 14px; font-weight: bold; margin: 0 0 12px; }
        .muted { color: #666; }
        .right { text-align: right; }
        .summary td { font-size: 13px; }
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
    <p class="subtitle">Bulletin de salaire enseignant</p>
    <p class="muted">
        Référence : {{ $row['reference'] }} |
        Période : {{ $months[$filters['mois']] ?? $filters['mois'] }} {{ $filters['annee'] }} |
        Base : {{ $row['payment_type'] === 'mensuel' ? 'Mensuel' : ($row['source'] === 'presence' ? 'Cahier de présence' : 'Émargements') }}
    </p>

    <table>
        <tr>
            <th>Enseignant</th>
            <td>{{ $enseignant->nom_prenom_enseignant }}</td>
            <th>Matricule</th>
            <td>{{ $enseignant->matricule ?: '-' }}</td>
        </tr>
        <tr>
            <th>Téléphone</th>
            <td>{{ $enseignant->telephone_enseignant ?: '-' }}</td>
            <th>Contrat</th>
            <td>{{ $row['contract'] }}</td>
        </tr>
        <tr>
            <th>Base de calcul</th>
            <td colspan="3">
                @if($row['contract'] === 'VCT')
                    {{ number_format($row['hours'], 2, ',', ' ') }} heure(s) x {{ number_format($row['hourly_rate'], 0, ',', ' ') }} FCFA
                @else
                    Salaire mensuel : {{ number_format($row['monthly_salary'], 0, ',', ' ') }} FCFA
                @endif
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <th>Montant à payer</th>
            <td class="right"><strong>{{ number_format($row['amount_due'], 0, ',', ' ') }} FCFA</strong></td>
        </tr>
        <tr>
            <th>Déjà versé</th>
            <td class="right">{{ number_format($row['paid'], 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <th>Reste à payer</th>
            <td class="right"><strong>{{ number_format($row['remaining'], 0, ',', ' ') }} FCFA</strong></td>
        </tr>
        <tr>
            <th>Statut</th>
            <td class="right">{{ $row['status'] }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Date de versement</th>
                <th class="right">Montant versé</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($row['salary']?->lignes ?? collect())->sortBy('date_paiement') as $line)
                <tr>
                    <td>{{ optional($line->date_paiement)->format('d/m/Y') }}</td>
                    <td class="right">{{ number_format($line->montant_verse, 0, ',', ' ') }} FCFA</td>
                </tr>
            @empty
                <tr>
                    <td colspan="2" class="muted">Aucun versement enregistré pour cette période.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:40px; text-align:right;">
        Établi par : {{ auth()->user()->nomPrenom ?? '' }}<br><br><br>
        Signature
    </div>
</body>
</html>
