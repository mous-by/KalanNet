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
        $schoolLabel = __('bulletins.complexe_scolaire_prefix', ['name' => $ecole->nomComplexe ?: $ecole->nomEcole]);
        if (in_array($ordre, ['fondamentale1', 'fondamentale2'], true) && !empty($ecole->nomFondamental)) {
            $subSchoolLabel = __('bulletins.ecole_fondamentale_prefix', ['name' => $ecole->nomFondamental]);
        } elseif ($ordre === 'secondaire' && !empty($ecole->nomLycee)) {
            $subSchoolLabel = __('bulletins.lycee_prefix', ['name' => $ecole->nomLycee]);
        } elseif ($ordre === 'secondaire' && !empty($ecole->nomProfessionnel)) {
            $subSchoolLabel = __('bulletins.ecole_professionnelle_prefix', ['name' => $ecole->nomProfessionnel]);
        } else {
            $subSchoolLabel = null;
        }
    } else {
        $schoolLabel = match (true) {
            in_array($ordre, ['fondamentale1', 'fondamentale2'], true) && !empty($ecole->nomFondamental) => __('bulletins.ecole_fondamentale_prefix', ['name' => $ecole->nomFondamental]),
            $ordre === 'secondaire' && !empty($ecole->nomLycee) => __('bulletins.lycee_prefix', ['name' => $ecole->nomLycee]),
            $ordre === 'secondaire' && !empty($ecole->nomProfessionnel) => __('bulletins.ecole_professionnelle_prefix', ['name' => $ecole->nomProfessionnel]),
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
                {{ __('bulletins.academie_de', ['name' => $academyName]) }}
                @if(in_array($ordre, ['fondamentale1', 'fondamentale2'], true) && $capName)
                    <div class="cap-texte">{{ __('bulletins.cap_de', ['name' => $capName]) }}</div>
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
            {{ strtoupper(__('bulletins.composition_du_mois', ['mois' => $apercu->mois_nom ?? '', 'annee' => $apercu->annee])) }}
        @else
            {{ strtoupper(__('bulletins.bulletin_du_trimestre', ['trimestre' => $apercu->nom_trimestre ?? '', 'annee' => $apercu->annee])) }}
        @endif
    </div>

    <table class="identity-table">
        <tr>
            <td>{{ __('bulletins.identity_nom_prenom') }}</td>
            <td>{{ __('bulletins.identity_sexe') }}</td>
            <td>{{ __('bulletins.th_classe') }}</td>
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
                    <th>{{ __('bulletins.pdf_th_matiere') }}</th>
                    <th>{{ __('bulletins.pdf_th_note') }}</th>
                    <th>{{ __('bulletins.pdf_th_coefficient') }}</th>
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
                    <td>{{ __('bulletins.pdf_totaux') }}</td>
                    <td>{{ number_format($totalNotes, 2) }}</td>
                    <td>{{ number_format($totalCoef, 2) }}</td>
                </tr>
            </tbody>
        @else
            <thead>
                <tr>
                    <th class="text-left">{{ __('bulletins.pdf_th_matiere') }}</th>
                    <th>M.Class</th>
                    <th>M.Compo</th>
                    <th>M.Gle</th>
                    <th>Coef</th>
                    <th>M.Coef</th>
                    <th>{{ __('bulletins.pdf_th_appreciation') }}</th>
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
                        <td class="text-left">{{ __('bulletins.pdf_conduite') }}</td>
                        <td></td>
                        <td></td>
                        <td>{{ number_format($note_conduite, 2) }}</td>
                        <td>1</td>
                        <td>{{ number_format($note_conduite, 2) }}</td>
                        <td>{{ $appreciation_conduite }}</td>
                    </tr>
                @endif

                <tr class="total-row">
                    <td>{{ __('bulletins.pdf_total') }}</td>
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
            <td><strong>{{ __('bulletins.pdf_moyenne_label') }}</strong> {{ number_format($moyenne_periode ?? 0, 2) }}</td>
            <td><strong>{{ __('bulletins.pdf_rang_label') }}</strong> {{ $rangAffiche }} / {{ $totalAffiche }}</td>
            <td><strong>{{ __('bulletins.pdf_moyenne_premier_label') }}</strong> {{ number_format($moyenne_premier ?? 0, 2) }}</td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td><strong>{{ __('bulletins.pdf_avis_directeur') }}</strong></td>
            <td><strong>{{ __('bulletins.pdf_le_tuteur') }}</strong></td>
        </tr>
    </table>
</div>
