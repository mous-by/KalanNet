<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #333; padding: 8px; }
        th { background: #f2f2f2; }
        .title { text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .muted { color: #666; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div style="display:flex; justify-content:space-between;">
        <div><strong>MINISTERE DE L'EDUCATION NATIONALE</strong><br>{{ $ecole?->academie }}<br>{{ $ecole?->cap }}</div>
        <div class="right"><strong>REPUBLIQUE DU MALI</strong><br>Un Peuple - Un But - Une Foi</div>
    </div>
    <p class="title">{{ $ecole?->nomEcole ?? 'École' }}</p>
    <p class="title">Reçu de paiement N° {{ $paiement->numero_recu }}</p>
    <p class="muted">Référence : {{ $paiement->reference }} | Date : {{ optional($paiement->date_paiement)->format('d/m/Y') }}</p>

    <table>
        <tr><th>Élève</th><td>{{ $paiement->eleve?->nom_eleve }} {{ $paiement->eleve?->prenom_eleve }}</td></tr>
        <tr><th>Classe</th><td>{{ $paiement->classe?->nom_classe }}</td></tr>
        <tr><th>Échéance</th><td>{{ $paiement->echeance?->libelle ?? $paiement->motif }}</td></tr>
        <tr><th>Montant payé</th><td><strong>{{ number_format($paiement->montant_paye ?? $paiement->montant, 0, ',', ' ') }} FCFA</strong></td></tr>
        <tr><th>Payeur</th><td>{{ $paiement->nom_payeur }} {{ $paiement->telephone ? ' - '.$paiement->telephone : '' }}</td></tr>
        <tr><th>Mode de règlement</th><td>{{ $paiement->mode_reglement }}</td></tr>
    </table>

    <div style="margin-top:40px; text-align:right;">
        Fait par : {{ auth()->user()->nomPrenom ?? '' }}<br><br><br>
        Signature
    </div>
</body>
</html>
