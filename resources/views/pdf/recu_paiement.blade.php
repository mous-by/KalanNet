<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement - {{ $ecole->nom_ecole ?? 'GESCO' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-left {
            float: left;
            width: 50%;
        }
        .header-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .clear {
            clear: both;
        }
        .title-box {
            text-align: center;
            margin: 20px 0;
            border: 2px solid #333;
            padding: 10px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .recu-number {
            font-size: 14px;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .footer {
            margin-top: 30px;
        }
        .footer-left {
            float: left;
            width: 50%;
        }
        .footer-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .signature-box {
            margin-top: 50px;
            height: 80px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <strong>{{ $ecole->nom_ecole ?? 'Établissement Scolaire' }}</strong><br>
            {{ $ecole->adresse ?? '' }}<br>
            Tel: {{ $ecole->telephone ?? '' }}<br>
            Email: {{ $ecole->email ?? '' }}
        </div>
        <div class="header-right">
            <strong>RÉPUBLIQUE DU MALI</strong><br>
            Un Peuple - Un But - Une Foi<br><br>
            Date: {{ date('d/m/Y') }}
        </div>
        <div class="clear"></div>
    </div>

    <div class="title-box">
        <div class="title">REÇU DE PAIEMENT</div>
        <div class="recu-number">N° {{ $paiement->numero_recu }}</div>
    </div>

    <div style="margin-bottom: 20px;">
        <strong>Élève:</strong> {{ $paiement->eleve->nom_eleve }} {{ $paiement->eleve->prenom_eleve }} ({{ $paiement->eleve->matricule }})<br>
        <strong>Classe:</strong> {{ $paiement->classe->nom_classe }}<br>
        <strong>Année Scolaire:</strong> {{ $paiement->annee_scolaire }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Libellé / Motif</th>
                <th>Période</th>
                <th style="text-align: right;">Montant Payé</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $paiement->motif }}</td>
                <td>{{ $paiement->mois ?? 'Scolarité' }}</td>
                <td style="text-align: right;">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">TOTAL PAYÉ</td>
                <td style="text-align: right;">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-bottom: 20px;">
        <strong>Montant en lettres:</strong> ..............................................................................................................
    </div>

    <div class="footer">
        <div class="footer-left">
            <strong>Le Payeur</strong>
            <div class="signature-box"></div>
            (Signature)
        </div>
        <div class="footer-right">
            <strong>La Comptabilité</strong>
            <div class="signature-box"></div>
            (Signature & Cachet)
        </div>
        <div class="clear"></div>
    </div>

    <div style="text-align: center; margin-top: 50px; font-size: 9px; color: #888; border-top: 1px solid #eee; padding-top: 10px;">
        Généré via GESCO - Alliance Team | {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>
