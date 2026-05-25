<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Certificat de transfert</title>
    <style>
        @page { margin: 24px 30px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            font-size: 12px;
            line-height: 1.55;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            width: 50%;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.45;
        }
        .right { text-align: right; }
        .stars { letter-spacing: 1px; }
        .school-name {
            margin-top: 12px;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .school-meta {
            text-align: center;
            font-size: 11px;
            margin-top: 2px;
        }
        .doc-title {
            width: 68%;
            margin: 28px auto 24px;
            padding: 8px 10px;
            text-align: center;
            border: 2px solid #000;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .reference-line {
            margin: 8px 0 24px;
            text-align: right;
            font-size: 11px;
        }
        .body-text {
            font-size: 13px;
            text-align: justify;
        }
        .field {
            display: inline-block;
            min-width: 150px;
            border-bottom: 1px dotted #000;
            font-weight: bold;
            padding: 0 3px 1px;
        }
        .field-long {
            min-width: 285px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 22px 0 18px;
        }
        .info-table th,
        .info-table td {
            border: 1px solid #000;
            padding: 8px 10px;
            vertical-align: top;
        }
        .info-table th {
            background: #f1f1f1;
            text-align: left;
            text-transform: uppercase;
            font-size: 11px;
        }
        .decision {
            border: 1px solid #000;
            padding: 10px 12px;
            margin-top: 18px;
        }
        .decision-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .footer-note {
            margin-top: 22px;
            text-align: justify;
        }
        .signature-zone {
            width: 100%;
            margin-top: 58px;
            border-collapse: collapse;
        }
        .signature-zone td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-weight: bold;
        }
        .signature-line {
            width: 72%;
            margin: 52px auto 0;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-weight: normal;
        }
    </style>
</head>
<body>
    @php
        $academyName = trim(preg_replace('/^\s*(academie|académie)\s+(d[’\']?|de)\s+/iu', '', (string) ($ecole?->academie ?? '')));
        $capName = trim(preg_replace('/^\s*cap\s+(d[’\']?|de)?\s*/iu', '', (string) ($ecole?->cap ?? '')));
        $fullName = trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve);
    @endphp

    <table class="header-table">
        <tr>
            <td>
                <strong>MINISTERE DE L'EDUCATION NATIONALE</strong><br>
                <span class="stars">********************</span><br>
                Académie d'Enseignement de {{ $academyName ?: '................................' }}<br>
                CAP de {{ $capName ?: '................................' }}
            </td>
            <td class="right">
                <strong>REPUBLIQUE DU MALI</strong><br>
                Un Peuple - Un But - Une Foi
            </td>
        </tr>
    </table>

    <div class="school-name">{{ $ecole?->nomEcole ?: $eleve->ecole?->nomEcole }}</div>
    <div class="school-meta">
        @if($ecole?->telephone)
            Tel : (+223) {{ $ecole->telephone }}
        @endif
        @if($ecole?->adresse)
            &nbsp; | &nbsp; {{ $ecole->adresse }}
        @endif
    </div>

    <div class="doc-title">Certificat de transfert</div>

    <div class="reference-line">
        N° {{ str_pad((string) $transfer->id_transfert, 4, '0', STR_PAD_LEFT) }}/{{ date('Y') }}
    </div>

    <p class="body-text">
        Je soussigné(e), Directeur / Directrice de l'établissement ci-dessus, certifie que l'élève
        <span class="field field-long">{{ $fullName }}</span>,
        matricule <span class="field">{{ $eleve->matricule ?: 'Non renseigné' }}</span>,
        né(e) le <span class="field">{{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : 'Non renseignée' }}</span>
        à <span class="field">{{ $eleve->lieu_naiss ?: 'Non renseigné' }}</span>,
        précédemment inscrit(e) en classe de
        <span class="field">{{ $eleve->classe?->nom_classe ?: 'Non renseignée' }}</span>
        pour l'année scolaire <span class="field">{{ $annee?->annee ?: 'Non renseignée' }}</span>,
        est transféré(e) vers <span class="field field-long">{{ $transfer->destination }}</span>.
    </p>

    <table class="info-table">
        <tr>
            <th>Motif du transfert</th>
            <td>{{ $transfer->motif }}</td>
        </tr>
        <tr>
            <th>Travail</th>
            <td>{{ $transfer->travail ?: 'Non renseigné' }}</td>
        </tr>
        <tr>
            <th>Conduite</th>
            <td>{{ $transfer->conduite }}</td>
        </tr>
    </table>

    <div class="decision">
        <div class="decision-title">Décision</div>
        L'élève est rayé(e) des effectifs actifs de l'établissement à compter de la présente fiche.
        Son dossier scolaire reste conservé dans les archives de l'école.
    </div>

    <p class="footer-note">
        En foi de quoi, le présent certificat de transfert lui est délivré pour servir et valoir ce que de droit.
    </p>

    <table class="signature-zone">
        <tr>
            <td>
                Le parent / tuteur
                <div class="signature-line">Signature</div>
            </td>
            <td>
                Fait à ____________________, le {{ date('d/m/Y') }}<br>
                Le Directeur / La Directrice
                <div class="signature-line">Cachet et signature</div>
            </td>
        </tr>
    </table>
</body>
</html>
