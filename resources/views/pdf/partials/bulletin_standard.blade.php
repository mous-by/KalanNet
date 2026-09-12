@php
    $schoolType = strtolower((string) ($ecole->typeEcole ?? ''));
    $academyName = $ecole->academieRef->nom_academie ?? $ecole->academie ?? '';
    $capName = $ecole->capRef->nom_cap ?? $ecole->cap ?? '';
    $academyName = trim(preg_replace('/^\s*(academie|académie)\s+(d[’\']?|de)\s+/iu', '', (string) $academyName));
    $capName = trim(preg_replace('/^\s*cap\s+(d[’\']?|de)?\s*/iu', '', (string) $capName));
    $logoBase64 = null;

    if (!empty($ecole->logoEcole)) {
        $logoPath = public_path($ecole->logoEcole);
        if (is_file($logoPath)) {
            $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $extension === 'png' ? 'image/png' : ($extension === 'webp' ? 'image/webp' : 'image/jpeg');
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }
    }

    if ($schoolType === 'complexe scolaire') {
        $schoolLabel = 'Complexe scolaire ' . ($ecole->nomComplexe ?: $ecole->nomEcole);
        if (in_array($ordre, ['fondamentale1', 'fondamentale2'], true) && !empty($ecole->nomFondamental)) {
            $subSchoolLabel = 'École Fondamentale : ' . $ecole->nomFondamental;
        } elseif ($ordre === 'secondaire' && !empty($ecole->nomLycee)) {
            $subSchoolLabel = 'Lycée : ' . $ecole->nomLycee;
        } elseif ($ordre === 'secondaire' && !empty($ecole->nomProfessionnel)) {
            $subSchoolLabel = 'École Professionnelle : ' . $ecole->nomProfessionnel;
        } else {
            $subSchoolLabel = null;
        }
    } else {
        $schoolLabel = match (true) {
            in_array($ordre, ['fondamentale1', 'fondamentale2'], true) && !empty($ecole->nomFondamental) => 'École Fondamentale : ' . $ecole->nomFondamental,
            $ordre === 'secondaire' && !empty($ecole->nomLycee) => 'Lycée : ' . $ecole->nomLycee,
            $ordre === 'secondaire' && !empty($ecole->nomProfessionnel) => 'École Professionnelle : ' . $ecole->nomProfessionnel,
            default => $ecole->nomEcole,
        };
        $subSchoolLabel = null;
    }

    $rangAffiche = $rang ?: '-';
    $totalAffiche = $total_eleves ?: '-';
@endphp

<div class="bulletin-container">
    <table class="header-table">
        <tr>
            <td class="left-content">
                MINISTERE DE L'EDUCATION NATIONALE<br>
                ********************<br>
                Académie d'Enseignement de {{ $academyName }}
                @if(in_array($ordre, ['fondamentale1', 'fondamentale2'], true) && $capName)
                    <div class="cap-texte">CAP de {{ $capName }}</div>
                @endif
            </td>
            <td class="right-content">
                <strong>REPUBLIQUE DU MALI</strong><br>
                UN PEUPLE - UN BUT - UNE FOI
            </td>
        </tr>
    </table>

    <table class="school-table">
        <tr>
            <td class="logo-cell">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo" class="school-logo">
                @endif
            </td>
            <td class="school-name-cell">
                <h4>{{ $schoolLabel }}</h4>
                @if($subSchoolLabel)
                    <div class="sub-school-name">{{ $subSchoolLabel }}</div>
                @endif
            </td>
        </tr>
    </table>

    @if(!empty($ecole->telephone))
        <p class="school-phone">Tel: (+223) {{ $ecole->telephone }}</p>
    @endif

    <div class="period-title">
        @if(!empty($apercu->mois_nom))
            Composition du mois {{ strtoupper($apercu->mois_nom ?? '') }} {{ $apercu->annee }}
        @else
            BULLETIN DU {{ strtoupper($apercu->nom_trimestre ?? '') }} {{ $apercu->annee }}
        @endif
    </div>

    <table class="identity-table">
        <tr>
            <td>Nom et prénom</td>
            <td>Sexe</td>
            <td>Classe</td>
        </tr>
        <tr>
            <td><strong>{{ $apercu->nom_eleve }} {{ $apercu->prenom_eleve }}</strong></td>
            <td><strong>{{ $apercu->genre_eleve }}</strong></td>
            <td><strong>{{ $apercu->nom_classe }}</strong></td>
        </tr>
    </table>

    <table class="notes-table">
        @if($ordre === 'fondamentale1')
            <thead>
                <tr>
                    <th>Matière</th>
                    <th>Note</th>
                    <th>Coefficient</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalCoef = $matieres->sum(fn ($matiere) => (float) ($matiere->coef ?? 0));
                    $totalNotes = $matieres->sum(fn ($matiere) => (float) ($matiere->M_Gle ?? 0));
                @endphp
                @foreach($matieres as $matiere)
                    <tr>
                        <td>{{ $matiere->nom_matiere }}</td>
                        <td>{{ number_format($matiere->M_Gle ?? 0, 2) }}</td>
                        <td>{{ number_format($matiere->coef ?? 0, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Totaux</td>
                    <td>{{ number_format($totalNotes, 2) }}</td>
                    <td>{{ number_format($totalCoef, 2) }}</td>
                </tr>
            </tbody>
        @else
            <thead>
                <tr>
                    <th class="text-left">Matière</th>
                    <th>M.Class</th>
                    <th>M.Compo</th>
                    <th>M.Gle</th>
                    <th>Coef</th>
                    <th>M.Coef</th>
                    <th>Appréciation</th>
                </tr>
            </thead>
            <tbody>
                <tr class="formula-row">
                    <td></td>
                    <td><strong>n/20</strong></td>
                    <td><strong>m/40</strong></td>
                    <td><strong>(n+m)/3</strong></td>
                    <td><strong>K</strong></td>
                    <td><strong>(n+m)/3 * k</strong></td>
                    <td></td>
                </tr>
                @foreach($matieres as $matiere)
                    <tr>
                        <td class="text-left">{{ $matiere->nom_matiere }}</td>
                        <td>{{ number_format($matiere->M_Class ?? 0, 2) }}</td>
                        <td>{{ number_format($matiere->M_Compo ?? 0, 2) }}</td>
                        <td>{{ number_format($matiere->M_Gle ?? 0, 2) }}</td>
                        <td>{{ number_format($matiere->coef ?? 0, 2) }}</td>
                        <td>{{ number_format($matiere->M_Coef ?? 0, 2) }}</td>
                        <td>{{ $matiere->appreciation }}</td>
                    </tr>
                @endforeach

                @if($note_conduite !== null)
                    <tr>
                        <td class="text-left">Conduite</td>
                        <td></td>
                        <td></td>
                        <td>{{ number_format($note_conduite, 2) }}</td>
                        <td>1</td>
                        <td>{{ number_format($note_conduite, 2) }}</td>
                        <td>{{ $appreciation_conduite }}</td>
                    </tr>
                @endif

                <tr class="total-row">
                    <td>Total</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ number_format($matieres->sum('coef') + ($note_conduite !== null ? 1 : 0), 2) }}</td>
                    <td>{{ number_format($matieres->sum('M_Coef') + ($note_conduite ?? 0), 2) }}</td>
                    <td></td>
                </tr>
            </tbody>
        @endif
    </table>

    <table class="summary-table">
        <tr>
            <td><strong>Moyenne :</strong> {{ number_format($moyenne_periode ?? 0, 2) }}</td>
            <td><strong>Rang :</strong> {{ $rangAffiche }} / {{ $totalAffiche }}</td>
            <td><strong>Moyenne du premier :</strong> {{ number_format($moyenne_premier ?? 0, 2) }}</td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td><strong>AVIS DU DIRECTEUR GÉNÉRAL</strong></td>
            <td><strong>LE TUTEUR</strong></td>
        </tr>
    </table>
</div>
