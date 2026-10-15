@php
    $schoolType = strtolower((string) ($ecole->typeEcole ?? ''));
    $academyName = $ecole->academieRef->nom_academie ?? $ecole->academie ?? '';
    $capName = $ecole->capRef->nom_cap ?? $ecole->cap ?? '';
    $academyName = \App\Support\NomAcademie::sansPrefixe($academyName);
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

    $estFondamental = \App\Support\Ordres::sousCap($ordre);
    $estTechnique = str_starts_with((string) $ordre, 'secondairetechnique');
    $estLycee = !$estTechnique && str_starts_with((string) $ordre, 'secondaire');
    // Libellé du cycle adapté au pays (ex: "Fondamentale II" au Mali, "Secondaire 1er cycle" ailleurs).
    $cycleLabel = trim(preg_replace('/\s*\(.*\)\s*$/', '', \App\Support\ExamenNational::ordresLabels($ecole)[$ordre] ?? ''));

    if ($schoolType === 'complexe scolaire') {
        $schoolLabel = __('bulletins.complexe_scolaire_prefix', ['name' => $ecole->nomComplexe ?: $ecole->nomEcole]);
        if ($estFondamental && !empty($ecole->nomFondamental)) {
            // Un seul nom pour la fondamentale : on précise le cycle (I ou II).
            $subSchoolLabel = __('bulletins.ecole_fondamentale_prefix', ['name' => $ecole->nomFondamental])
                . ($cycleLabel !== '' ? ' — ' . $cycleLabel : '');
        } elseif ($estLycee && !empty($ecole->nomLycee)) {
            $subSchoolLabel = __('bulletins.lycee_prefix', ['name' => $ecole->nomLycee]);
        } elseif ($estTechnique && !empty($ecole->nomProfessionnel)) {
            $subSchoolLabel = __('bulletins.ecole_professionnelle_prefix', ['name' => $ecole->nomProfessionnel]);
        } else {
            $subSchoolLabel = $cycleLabel !== '' ? $cycleLabel : null;
        }
    } else {
        $schoolLabel = match (true) {
            $estFondamental && !empty($ecole->nomFondamental) => __('bulletins.ecole_fondamentale_prefix', ['name' => $ecole->nomFondamental]),
            $estLycee && !empty($ecole->nomLycee) => __('bulletins.lycee_prefix', ['name' => $ecole->nomLycee]),
            $estTechnique && !empty($ecole->nomProfessionnel) => __('bulletins.ecole_professionnelle_prefix', ['name' => $ecole->nomProfessionnel]),
            default => $ecole->nomEcole,
        };
        $subSchoolLabel = null;
    }

    $rangAffiche = $rang ?: '-';
    // Secondaire : matières principales puis secondaires, libellé vertical à droite.
    $avecGroupes = !empty($groupes ?? []);
    $totalAffiche = $total_eleves ?: '-';
@endphp

<div class="bulletin-container">
    <table class="header-table">
        <tr>
            <td class="left-content">
                @if($ecole->pays?->entete_document_gauche)
                    {{ $ecole->pays->entete_document_gauche }}<br>
                    ********************<br>
                @endif
                {{ __('bulletins.academie_de', ['name' => $academyName]) }}
                @if(\App\Support\Ordres::sousCap($ordre) && $capName)
                    <div class="cap-texte">{{ __('bulletins.cap_de', ['name' => $capName]) }}</div>
                @endif
            </td>
            <td class="right-content">
                @if($ecole->pays?->entete_document_droite)
                    {{-- Première ligne (le pays) en gras, la devise en dessous en normal. --}}
                    @php $lignesDroite = preg_split('/\r\n|\r|\n/', trim($ecole->pays->entete_document_droite)); @endphp
                    <strong>{{ array_shift($lignesDroite) }}</strong>
                    @foreach($lignesDroite as $ligne)<br>{{ $ligne }}@endforeach
                @endif
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
            <td><strong>{{ $apercu->prenom_eleve }} {{ $apercu->nom_eleve }}</strong></td>
            <td><strong>{{ $apercu->genre_eleve }}</strong></td>
            <td><strong>{{ $apercu->nom_classe }}</strong></td>
        </tr>
    </table>

    <table class="notes-table">
        @if(\App\Support\Ordres::surDix($ordre))
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
                        <td>{{ \App\Support\ArabePdf::texte($matiere->nom_matiere) }}</td>
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
                    @if($avecGroupes)<th style="width:16px"></th>@endif
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
                    @if($avecGroupes)<td></td>@endif
                </tr>
                @if($avecGroupes)
                    @foreach($groupes as $cleGroupe => $groupe)
                        @foreach($groupe->matieres as $matiere)
                            <tr>
                                <td class="text-left">{{ !empty($matiere->est_conduite) ? __('bulletins.pdf_conduite') : \App\Support\ArabePdf::texte($matiere->nom_matiere) }}</td>
                                <td>{{ !empty($matiere->est_conduite) ? '' : number_format($matiere->M_Class ?? 0, 2) }}</td>
                                <td>{{ !empty($matiere->est_conduite) ? '' : number_format($matiere->M_Compo ?? 0, 2) }}</td>
                                <td>{{ number_format($matiere->M_Gle ?? 0, 2) }}</td>
                                <td>{{ !empty($matiere->est_conduite) ? '1' : number_format($matiere->coef ?? 0, 2) }}</td>
                                <td>{{ number_format($matiere->M_Coef ?? 0, 2) }}</td>
                                <td>{{ $matiere->appreciation }}</td>
                                @if($loop->first)
                                    @include('pdf.bulletins._groupe_vertical', ['cle' => $cleGroupe, 'lignes' => $groupe->matieres->count() + 2])
                                @endif
                            </tr>
                        @endforeach
                        <tr class="total-row" style="background:#f1f1f1">
                            <td class="text-left">Total partiel</td><td></td><td></td><td></td>
                            <td>{{ number_format($groupe->total_coef, 2) }}</td>
                            <td>{{ number_format($groupe->total, 2) }}</td>
                            <td></td>
                        </tr>
                        <tr class="total-row" style="background:#f1f1f1">
                            <td class="text-left">Moy. partielle</td><td></td><td></td><td></td><td></td>
                            <td>{{ number_format($groupe->moyenne, 2) }}</td>
                            <td>{{ \App\Services\Bulletins\CalculBulletin::appreciation($groupe->moyenne, $ordre) }}</td>
                        </tr>
                    @endforeach
                @else
                @foreach($matieres as $matiere)
                    <tr>
                        <td class="text-left">{{ \App\Support\ArabePdf::texte($matiere->nom_matiere) }}</td>
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
                @endif

                <tr class="total-row">
                    <td>{{ __('bulletins.pdf_total') }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>{{ number_format($matieres->sum('coef') + ($note_conduite !== null ? 1 : 0), 2) }}</td>
                    <td>{{ number_format($matieres->sum('M_Coef') + ($note_conduite ?? 0), 2) }}</td>
                    <td></td>
                    @if($avecGroupes)<td></td>@endif
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
        @if(!empty($annuel))
            <tr>
                <td><strong>Moyenne annuelle :</strong> {{ number_format($annuel['moyenne'], 2) }}</td>
                <td><strong>Rang annuel :</strong> {{ $annuel['rang'] }} / {{ $annuel['total'] }}</td>
                <td>({{ $annuel['libelle'] }})</td>
            </tr>
            @if(!empty($tendance))
                <tr>
                    <td colspan="3"><strong>Tendance :</strong> {{ collect($tendance['retenues'])->pluck('libelle')->implode(' ou ') }}
                        ({{ collect($tendance['retenues'][0]['matieres'])->map(fn ($m, $nom) => \App\Support\ArabePdf::texte($nom).' '.number_format($m, 2))->implode(' · ') }})</td>
                </tr>
            @endif
        @endif
    </table>

    <table class="signature-table">
        <tr>
            <td><strong>{{ __('bulletins.pdf_avis_directeur') }}</strong></td>
            <td><strong>{{ __('bulletins.pdf_le_tuteur') }}</strong></td>
        </tr>
    </table>
</div>
