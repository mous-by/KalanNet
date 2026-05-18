<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; margin: 0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .line { border-top: 1px dashed #111; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .title { font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .amount { font-weight: bold; font-size: 12px; }
        .qr { width: 92px; height: 92px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">{{ $ecole?->nomEcole ?? 'École' }}</div>
        <div>{{ $ecole?->adresseEcole ?? $ecole?->adresse ?? '' }}</div>
        <div>{{ $ecole?->telephoneEcole ?? $ecole?->telephone ?? '' }}</div>
        <div class="line"></div>
        <div><strong>RECU DE PAIEMENT</strong></div>
        <div>N° {{ $paiement->numero_recu }}</div>
        <div>Ref : {{ $paiement->reference }}</div>
    </div>

    <div class="line"></div>
    <table>
        <tr><td>Date</td><td class="right">{{ optional($paiement->date_paiement)->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Élève</td><td class="right">{{ $paiement->eleve?->nom_eleve }} {{ $paiement->eleve?->prenom_eleve }}</td></tr>
        <tr><td>Classe</td><td class="right">{{ $paiement->classe?->nom_classe }}</td></tr>
        <tr><td>Motif</td><td class="right">{{ $paiement->motif }}</td></tr>
        <tr><td>Payeur</td><td class="right">{{ $paiement->nom_payeur }}</td></tr>
        <tr><td>Tél.</td><td class="right">{{ $paiement->telephone }}</td></tr>
    </table>
    <div class="line"></div>
    <table>
        <tr>
            <td>Total payé</td>
            <td class="right amount">{{ number_format((float) ($paiement->montant_paye ?? $paiement->montant), 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="center">
        <img src="{{ $qrCode }}" class="qr" alt="QR Code">
        <div>Scannez pour vérifier le reçu</div>
    </div>
    <div class="line"></div>
    <div class="center">
        Merci pour votre paiement<br>
        KalanNet - {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
