<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des élèves</title>
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
        .genre {
            width: 58px;
        }
        .date {
            width: 78px;
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
        $filles = $eleves->where('genre_eleve', 'Féminin')->count();
        $garcons = $eleves->where('genre_eleve', 'Masculin')->count();
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
    <div class="title">Liste des élèves inscrits</div>

    <table class="context">
        <tr>
            <td><strong>Classe :</strong> {{ $classe->nom_classe }}</td>
            <td><strong>Année scolaire :</strong> {{ $annee->annee }}</td>
            <td><strong>Date d’édition :</strong> {{ now()->format('d/m/Y') }}</td>
        </tr>
    </table>

    <div class="stats">
        Total : <strong>{{ $eleves->count() }}</strong>
        &nbsp; | &nbsp; Garçons : <strong>{{ $garcons }}</strong>
        &nbsp; | &nbsp; Filles : <strong>{{ $filles }}</strong>
    </div>

    <table class="list">
        <thead>
            <tr>
                <th class="num">N°</th>
                <th class="matricule">Matricule</th>
                <th>Prénom et nom</th>
                <th class="genre">Genre</th>
                <th class="date">Naissance</th>
                <th>Lieu de naissance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($eleves as $index => $eleve)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td class="matricule">{{ $eleve->matricule }}</td>
                    <td>{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</td>
                    <td class="genre">{{ $eleve->genre_eleve }}</td>
                    <td class="date">{{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : '' }}</td>
                    <td>{{ $eleve->lieu_naiss }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <div>La Direction</div>
            <div class="line">Signature et cachet</div>
        </div>
    </div>
</body>
</html>
