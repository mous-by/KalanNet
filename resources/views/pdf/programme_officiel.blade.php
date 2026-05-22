<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Programme officiel</title>
    <style>
        @page {
            margin: 8mm 8mm 10mm 8mm;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 10px;
            line-height: 1.25;
            margin: 0;
            background-color: #ffffff;
        }
        .header {
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 7px;
        }
        .header td {
            vertical-align: middle;
        }
        .school-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0;
        }
        .school-subtitle {
            font-size: 8px;
            color: #475569;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f766e;
            margin: 0;
            text-align: right;
        }
        .meta-info {
            font-size: 9px;
            color: #64748b;
            text-align: right;
            margin-top: 3px;
        }
        .classe-title {
            background-color: #e0f2f1;
            border: 1px solid #94a3b8;
            color: #0f172a;
            font-size: 12px;
            font-weight: bold;
            padding: 7px 9px;
            margin-top: 9px;
            text-transform: uppercase;
        }
        .matiere-block {
            border: 1px solid #cbd5e1;
            margin-top: 7px;
            page-break-inside: avoid;
        }
        .matiere-title {
            background-color: #f8fafc;
            color: #0f766e;
            font-weight: bold;
            font-size: 11px;
            padding: 6px 8px;
            border-bottom: 1px solid #cbd5e1;
        }
        .lecon-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .lecon-table th {
            background-color: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            color: #334155;
            font-size: 9px;
            padding: 5px;
            text-align: left;
        }
        .lecon-table td {
            border-top: 1px solid #e2e8f0;
            padding: 5px;
            vertical-align: top;
        }
        .numero-col {
            width: 48px;
            text-align: center;
            color: #0f766e;
            font-weight: bold;
        }
        .empty {
            color: #64748b;
            font-style: italic;
            padding: 10px;
        }
    </style>
</head>
<body>
    @php
        $first = $programmes->flatten(1)->first();
        $ordre = trim($first->classeOfficielle->ordre_enseignement ?? '');
        $typeEcole = strtolower(trim($ecole->typeEcole ?? ''));

        $nomAffiche = $ecole->nomEcole ?? 'Programme officiel';
        if ($ecole) {
            if ($typeEcole === 'complexe scolaire') {
                $nomAffiche = 'Complexe scolaire ' . ($ecole->nomComplexe ?? '');
                if (($ordre === 'fondamentale1' || $ordre === 'fondamentale2') && !empty($ecole->nomFondamental)) {
                    $nomAffiche = 'École Fondamentale : ' . $ecole->nomFondamental;
                } elseif ($ordre === 'secondaire') {
                    if (!empty($ecole->nomLycee)) {
                        $nomAffiche = 'Lycée : ' . $ecole->nomLycee;
                    } elseif (!empty($ecole->nomProfessionnel)) {
                        $nomAffiche = 'École Professionnelle : ' . $ecole->nomProfessionnel;
                    }
                }
            } elseif (($ordre === 'fondamentale1' || $ordre === 'fondamentale2') && !empty($ecole->nomFondamental)) {
                $nomAffiche = 'École Fondamentale : ' . $ecole->nomFondamental;
            } elseif ($ordre === 'secondaire' && !empty($ecole->nomLycee)) {
                $nomAffiche = 'Lycée : ' . $ecole->nomLycee;
            } elseif ($ordre === 'secondaire' && !empty($ecole->nomProfessionnel)) {
                $nomAffiche = 'École Professionnelle : ' . $ecole->nomProfessionnel;
            }
        }

        $logoFile = basename($ecole->logoEcole ?? '');
        $logoPath = public_path('images_ecoles/' . $logoFile);
        $logoExists = !empty($logoFile) && file_exists($logoPath);
    @endphp

    <table class="header">
        <tr>
            <td width="10%">
                @if($logoExists)
                    <img src="{{ $logoPath }}" width="50" height="50" style="border-radius: 50%;">
                @endif
            </td>
            <td width="48%" style="padding-left: 10px;">
                <div class="school-title">{{ $nomAffiche }}</div>
                <div class="school-subtitle">
                    @if($ecole && $ecole->telephone)
                        Téléphone : {{ $ecole->telephone }}
                    @endif
                </div>
            </td>
            <td width="42%">
                <div class="doc-title">PROGRAMME OFFICIEL</div>
                @if($isTeacherPdf && $specialite)
                    <div class="meta-info">Spécialité : {{ $specialite }}</div>
                @endif
                <div class="meta-info">Généré le {{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    @foreach($programmes as $items)
        @php($first = $items->first())
        <div class="classe-title">
            Programme officiel {{ $first->classeOfficielle->nom_classe_officielle ?? '' }}
            @if(!empty($first->classeOfficielle->ordre_enseignement))
                - {{ $first->classeOfficielle->ordre_enseignement }}
            @endif
        </div>

        @foreach($items as $programmeClasse)
            <div class="matiere-block">
                <div class="matiere-title">{{ $programmeClasse->matiere->nom_matiere ?? 'Matière' }}</div>
                <table class="lecon-table">
                    <thead>
                        <tr>
                            <th class="numero-col">N°</th>
                            <th>Leçons</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programmeClasse->lecons->sortBy('numero') as $lecon)
                            <tr>
                                <td class="numero-col">{{ $lecon->numero }}</td>
                                <td>{{ $lecon->titre }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="empty">Aucune leçon renseignée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach
    @endforeach
</body>
</html>
