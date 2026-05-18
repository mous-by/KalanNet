<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 5px; }
        th { background: #f2f2f2; }
        h2 { text-align: center; text-transform: uppercase; }
    </style>
</head>
<body>
    <h2>Historique des paiements scolaires</h2>
    <table>
        <thead>
            <tr>
                <th>Élève</th><th>Classe</th><th>Année</th><th>Statut financier</th><th>Mode</th><th>Total</th><th>Payé</th><th>Reste</th><th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($plans as $plan)
                @php($resume = $plan->resume_paiement)
                <tr>
                    <td>{{ $plan->eleve?->nom_eleve }} {{ $plan->eleve?->prenom_eleve }}</td>
                    <td>{{ $plan->classe?->nom_classe }}</td>
                    <td>{{ $plan->anneeScolaire?->annee }}</td>
                    <td>{{ $plan->statut_paiement }}</td>
                    <td>{{ $plan->mode_paiement }}</td>
                    <td>{{ number_format($resume['montant_attendu'], 0, ',', ' ') }}</td>
                    <td>{{ number_format($resume['montant_paye'], 0, ',', ' ') }}</td>
                    <td>{{ number_format($resume['reste'], 0, ',', ' ') }}</td>
                    <td>{{ $resume['statut'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
