<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de notes</title>
    <style>
        @page { margin: 26px 28px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.35;
        }
        .header { width: 100%; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px; }
        .header td { vertical-align: top; }
        .text-right { text-align: right; }
        .title {
            margin: 4px 0 8px;
            text-align: center;
            font-size: 19px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .school-name {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 12px 0 3px;
        }
        .context {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .context td {
            border: 1px solid #d1d5db;
            padding: 7px 9px;
        }
        .context strong {
            color: #374151;
        }
        .stats {
            margin-bottom: 12px;
            font-size: 11px;
            color: #374151;
        }
        .list {
            width: 100%;
            border-collapse: collapse;
        }
        .list th {
            background: #e5e7eb;
            color: #111827;
            border: 1px solid #9ca3af;
            padding: 6px 5px;
            text-align: left;
            font-weight: 700;
        }
        .list td {
            border: 1px solid #d1d5db;
            padding: 5px;
            vertical-align: middle;
        }
        .list tbody tr:nth-child(even) td {
            background: #f9fafb;
        }
        .num {
            width: 28px;
            text-align: center;
        }
        .matricule {
            width: 85px;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 10px;
        }
        .note {
            width: 90px;
            text-align: center;
            font-weight: 700;
        }
        .note-empty {
            color: #9ca3af;
            font-weight: 400;
            font-style: italic;
        }
        .note-fail {
            color: #b91c1c;
        }
        .note-pass {
            color: #15803d;
        }
        .footer {
            margin-top: 34px;
            width: 100%;
            font-size: 11px;
        }
        .signature {
            width: 40%;
            float: right;
            text-align: center;
        }
        .line {
            margin-top: 42px;
            border-top: 1px solid #111827;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    @php
        $notesSaisies = $details->whereNotNull('note');
        $moyenne = $notesSaisies->count() > 0 ? $notesSaisies->avg('note') : null;
        $passMark = $maxNote / 2;
    @endphp

    <table class="header">
        <tr>
            <td width="50%">
                <strong>MINISTÈRE DE L'ÉDUCATION NATIONALE</strong><br>
                Académie d'Enseignement de {{ $ecole?->academie ?? '' }}<br>
                CAP de {{ $ecole?->cap ?? '' }}
            </td>
            <td width="50%" class="text-right">
                <strong>RÉPUBLIQUE DU MALI</strong><br>
                Un Peuple - Un But - Une Foi
            </td>
        </tr>
    </table>

    <div class="school-name">{{ $ecole?->nomEcole ?? '' }}</div>
    <div class="title">Fiche de notes</div>

    <table class="context">
        <tr>
            <td><strong>Classe :</strong> {{ $classe->nom_classe }}</td>
            <td><strong>Matière :</strong> {{ $matiere->nom_matiere }}</td>
            <td><strong>Date d’édition :</strong> {{ now()->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td><strong>Évaluation :</strong> {{ $evaluation->libeller }}</td>
            <td><strong>Date de l’évaluation :</strong> {{ \Carbon\Carbon::parse($evaluation->date_evaluation)->format('d/m/Y') }}</td>
            <td><strong>Notée sur :</strong> {{ number_format($maxNote, 0, ',', ' ') }}</td>
        </tr>
    </table>

    <div class="stats">
        Élèves : <strong>{{ $details->count() }}</strong>
        &nbsp; | &nbsp; Notes saisies : <strong>{{ $notesSaisies->count() }}</strong>
        &nbsp; | &nbsp; Moyenne de la classe : <strong>{{ $moyenne !== null ? number_format($moyenne, 2, ',', ' ') : '—' }}</strong>
    </div>

    <table class="list">
        <thead>
            <tr>
                <th class="num">N°</th>
                <th class="matricule">Matricule</th>
                <th>Nom et prénom</th>
                <th class="note">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $line)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td class="matricule">{{ $line->eleve->matricule ?? '' }}</td>
                    <td>{{ $line->eleve->nom_eleve ?? '' }} {{ $line->eleve->prenom_eleve ?? '' }}</td>
                    <td class="note">
                        @if($line->note === null)
                            <span class="note-empty">Non saisie</span>
                        @else
                            <span class="{{ $line->note >= $passMark ? 'note-pass' : 'note-fail' }}">{{ number_format($line->note, 2, ',', ' ') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <div>L'Enseignant(e)</div>
            <div class="line">Signature</div>
        </div>
    </div>
</body>
</html>
