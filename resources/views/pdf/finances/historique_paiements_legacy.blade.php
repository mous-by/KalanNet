<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; }
        h2 { text-align: center; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px; }
        th { background: #eee; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Historique des paiements élèves</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Référence</th>
                <th>Reçu</th>
                <th>Élève</th>
                <th>Classe</th>
                <th>Motif</th>
                <th>Payeur</th>
                <th class="right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paiements as $paiement)
                <tr>
                    <td>{{ optional($paiement->date_paiement)->format('d/m/Y') }}</td>
                    <td>{{ $paiement->reference }}</td>
                    <td>{{ $paiement->numero_recu }}</td>
                    <td>{{ $paiement->eleve?->nom_eleve }} {{ $paiement->eleve?->prenom_eleve }}</td>
                    <td>{{ $paiement->classe?->nom_classe }}</td>
                    <td>{{ $paiement->motif }}</td>
                    <td>{{ $paiement->nom_payeur }}</td>
                    <td class="right">{{ number_format((float) ($paiement->montant_paye ?? $paiement->montant), 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
