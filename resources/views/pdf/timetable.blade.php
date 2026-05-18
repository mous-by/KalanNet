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
            color: #334155;
            font-size: 9px;
            line-height: 1.1;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .header {
            width: 100%;
            margin-bottom: 5px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 5px;
        }
        .header td {
            vertical-align: middle;
        }
        .school-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            margin: 0;
        }
        .school-subtitle {
            font-size: 8px;
            color: #64748b;
            margin: 2px 0 0 0;
        }
        .title-block {
            text-align: center;
        }
        .doc-title {
            font-size: 15px;
            font-weight: 800;
            color: #1e3a8a;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .meta-info {
            font-size: 10px;
            font-weight: bold;
            color: #475569;
            margin-top: 2px;
        }
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            table-layout: fixed;
        }
        .grid-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            border: 1px solid #cbd5e1;
            padding: 4px 2px;
            text-align: center;
        }
        .grid-table td {
            border: 1px solid #cbd5e1;
            text-align: center;
            vertical-align: middle;
            padding: 3px 2px;
            height: 38px;
            word-wrap: break-word;
        }
        .hour-col {
            background-color: #f8fafc;
            color: #2563eb;
            font-weight: bold;
            font-size: 8.5px;
            width: 75px;
        }
        .course-matiere {
            font-weight: bold;
            color: #1e40af;
            font-size: 9px;
            margin-bottom: 2px;
        }
        .course-enseignant {
            color: #475569;
            font-size: 8px;
        }
        
        /* Alliance Parity Cross Case */
        .croix-case {
            display: inline-block;
            width: 16px;
            height: 16px;
            position: relative;
            vertical-align: middle;
            opacity: 0.35;
        }
        .croix-case::before, .croix-case::after {
            content: '';
            position: absolute;
            background: #475569;
            width: 100%;
            height: 1.5px;
            top: 50%;
            left: 0;
            margin-top: -0.75px;
        }
        .croix-case::before { transform: rotate(45deg); }
        .croix-case::after { transform: rotate(-45deg); }

        .recess-row {
            background-color: #fffbeb;
        }
        .recess-hour {
            color: #d97706;
            font-weight: bold;
            font-size: 8px;
            border: 1px solid #fde68a !important;
            background-color: #fffbeb;
        }
        .recess-content {
            background-color: #fffbeb;
            color: #b45309;
            font-weight: bold;
            font-size: 10px;
            letter-spacing: 4px;
            text-align: center;
            border: 1px solid #fde68a !important;
            padding: 4px 0;
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
            @php
                $currentEndTime = "08:00";
            @endphp
            @foreach($heures as $heure)
                @php
                    $recessDuration = isset($recesses[$heure]) && (int)$recesses[$heure] > 0 ? (int)$recesses[$heure] : 0;
                    $hasRecessAfter = $recessDuration > 0;

                    $rowStartTime = $currentEndTime;
                    $rowEndTime = date('H:i', strtotime($rowStartTime . ' +1 hour'));
                    $currentEndTime = $rowEndTime;
                @endphp
                <tr>
                    <td class="hour-col">
                        {{ $rowStartTime }} - {{ $rowEndTime }}
                    </td>
                    @foreach($jours as $jour)
                        <td>
                            @php
                                $courses = $timetable[$jour] ?? collect();
                                $course = $courses->first(fn ($c) => substr($c->heure_debut, 0, 5) <= $heure && substr($c->heure_fin, 0, 5) > $heure);
                            @endphp
                            @if($course)
                                <div class="course-matiere">{{ $course->matiere->nom_matiere ?? 'Matière' }}</div>
                                <div class="course-enseignant">{{ $course->enseignant->nom_prenom_enseignant ?? '' }}</div>
                            @else
                                <span class="croix-case"></span>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @if($hasRecessAfter)
                    @php
                        $recessStartTime = $currentEndTime;
                        $recessEndTime = date('H:i', strtotime($recessStartTime . ' +' . $recessDuration . ' minutes'));
                        $currentEndTime = $recessEndTime;
                    @endphp
                    <tr class="recess-row">
                        <td class="recess-hour">
                            {{ $recessStartTime }} - {{ $recessEndTime }}
                        </td>
                        <td colspan="6" class="recess-content">
                            ☕ RÉCRÉATION ({{ $recessDuration }} MIN) ☕
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

</body>
</html>
