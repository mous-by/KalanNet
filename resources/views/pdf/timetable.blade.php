<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Emploi du temps - {{ $selectedClasse->nom_classe }}</title>
    <style>
        @page {
            margin: 5mm 5mm 5mm 5mm;
            size: a4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 8.5px;
            line-height: 1.08;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .header {
            width: 100%;
            margin-bottom: 4px;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 5px;
        }
        .header td {
            vertical-align: middle;
        }
        .school-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0;
        }
        .school-subtitle {
            font-size: 7.5px;
            color: #475569;
            margin: 2px 0 0 0;
        }
        .title-block {
            text-align: center;
        }
        .doc-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f766e;
            margin: 0;
        }
        .meta-info {
            font-size: 9px;
            font-weight: bold;
            color: #334155;
            margin-top: 2px;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            table-layout: fixed;
        }
        .grid-table th {
            background-color: #e0f2f1;
            color: #0f172a;
            font-weight: bold;
            font-size: 8.5px;
            text-transform: uppercase;
            border: 1px solid #94a3b8;
            padding: 4px 2px;
            text-align: center;
        }
        .grid-table td {
            border: 1px solid #94a3b8;
            text-align: center;
            vertical-align: middle;
            padding: 2px;
            height: 30px;
            word-wrap: break-word;
        }
        .hour-col {
            background-color: #f8fafc;
            color: #0f766e;
            font-weight: bold;
            font-size: 8px;
            width: 72px;
        }
        .course-matiere {
            font-weight: bold;
            color: #0f172a;
            font-size: 8.5px;
            margin-bottom: 1px;
        }
        .course-enseignant {
            color: #475569;
            font-size: 7px;
        }

        .recess-row {
            background-color: #fef3c7;
        }
        .recess-hour {
            color: #92400e;
            font-weight: bold;
            font-size: 7.5px;
            border: 1px solid #fde68a !important;
            background-color: #fffbeb;
        }
        .recess-content {
            background-color: #fffbeb;
            color: #92400e;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
            border: 1px solid #fde68a !important;
            padding: 3px 0;
        }
    </style>
</head>
<body>

    @php
        // Construct Dynamic Alliance School Header Name
        $typeEcole = strtolower(trim($ecole->typeEcole ?? ''));
        $ordre = trim($selectedClasse->ordreEnseignement ?? '');

        $nomAffiche = '';
        if ($typeEcole === "complexe scolaire") {
            $nomAffiche = "Complexe scolaire " . ($ecole->nomComplexe ?? '');
            if ($ordre === "fondamentale1" || $ordre === "fondamentale2") {
                if (!empty($ecole->nomFondamental)) {
                    $nomAffiche = "École Fondamentale : " . $ecole->nomFondamental;
                }
            } elseif ($ordre === "secondaire") {
                if (!empty($ecole->nomLycee)) {
                    $nomAffiche = "Lycée : " . $ecole->nomLycee;
                } elseif (!empty($ecole->nomProfessionnel)) {
                    $nomAffiche = "École Professionnelle : " . $ecole->nomProfessionnel;
                }
            }
        } else {
            if (($ordre === "fondamentale1" || $ordre === "fondamentale2") && !empty($ecole->nomFondamental)) {
                $nomAffiche = "École Fondamentale : " . $ecole->nomFondamental;
            } elseif ($ordre === "secondaire" && !empty($ecole->nomLycee)) {
                $nomAffiche = "Lycée : " . $ecole->nomLycee;
            } elseif ($ordre === "secondaire" && !empty($ecole->nomProfessionnel)) {
                $nomAffiche = "École Professionnelle : " . $ecole->nomProfessionnel;
            } else {
                $nomAffiche = $ecole->nomEcole ?? '';
            }
        }

        // Dynamic Logo
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
            <td width="45%" style="text-align: left; padding-left: 10px;">
                <div class="school-title">{{ $nomAffiche }}</div>
                <div class="school-subtitle">
                    @if($ecole && $ecole->telephone)
                        Téléphone : {{ $ecole->telephone }}
                    @endif
                </div>
            </td>
            <td width="45%" style="text-align: right; vertical-align: top;">
                <div class="doc-title">EMPLOI DU TEMPS</div>
                <div class="meta-info">CLASSE : {{ $selectedClasse->nom_classe }}</div>
                <div class="meta-info" style="font-size: 8px; color: #64748b; margin-top: 3px;">
                    Année Scolaire : {{ $selectedAnnee->annee }}
                </div>
            </td>
        </tr>
    </table>

    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 80px;">Heures</th>
                @foreach($jours as $jour)
                    <th>{{ $jour }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($heures as $heure => $plage)
                @php
                    $rowStartTime = $plage['debut'] ?? $heure;
                    $rowEndTime = $plage['fin'] ?? date('H:i', strtotime($rowStartTime . ' +1 hour'));
                    $recessDuration = isset($recesses[$heure]) && (int)$recesses[$heure] > 0 ? (int)$recesses[$heure] : 0;
                    $hasRecessAfter = $recessDuration > 0;
                @endphp
                <tr>
                    <td class="hour-col">
                        {{ $rowStartTime }} - {{ $rowEndTime }}
                    </td>
                    @foreach($jours as $jour)
                        <td>
                            @php
                                $courses = $timetable[$jour] ?? collect();
                                $course = $courses->first(fn ($c) => substr($c->heure_debut, 0, 5) === $rowStartTime);
                            @endphp
                            @if($course)
                                <div class="course-matiere">{{ $course->matiere->nom_matiere ?? 'Matière' }}</div>
                                <div class="course-enseignant">{{ $course->enseignant->nom_prenom_enseignant ?? '' }}</div>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @if($hasRecessAfter)
                    @php
                        $recessStartTime = $rowEndTime;
                        $recessEndTime = date('H:i', strtotime($recessStartTime . ' +' . $recessDuration . ' minutes'));
                    @endphp
                    <tr class="recess-row">
                        <td class="recess-hour">
                            {{ $recessStartTime }} - {{ $recessEndTime }}
                        </td>
                        <td colspan="6" class="recess-content">
                            RÉCRÉATION ({{ $recessDuration }} MIN)
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="7" style="height: 45px; color: #64748b;">Aucun horaire renseigné pour cette classe.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
