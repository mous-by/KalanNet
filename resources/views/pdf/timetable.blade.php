@php
    // ---- Modèle choisi (6 modèles, voir App\Support\ModelesEdt) ; « Détaillé » par défaut.
    $ordre = trim((string) ($selectedClasse->ordreEnseignement ?? ''));
    $m = \App\Support\ModelesEdt::pour($modele ?? null, $ordre);
    [$accent, $clair] = $m['couleurs'];
    // Noir et blanc par défaut (la plupart des écoles n'ont pas d'imprimante couleur) ; ?couleur=1 sinon.
    $enCouleur = !empty($couleur);
    if (!$enCouleur) {
        [$accent, $clair] = ['#111111', '#f1f1f1'];
    }
    $bilingue = !empty($m['bilingue']);
    $decor = $m['decor'] ?? null;
    $t = fn (string $fr, string $en) => $bilingue ? $fr.' / '.$en : $fr;
    $joursEn = ['Lundi' => 'Monday', 'Mardi' => 'Tuesday', 'Mercredi' => 'Wednesday', 'Jeudi' => 'Thursday', 'Vendredi' => 'Friday', 'Samedi' => 'Saturday'];

    // ---- École : nom selon le cycle (complexe scolaire), logo, en-tête officiel comme les bulletins.
    $estFondamental = \App\Support\Ordres::sousCap($ordre);
    $estTechnique = str_starts_with($ordre, 'secondairetechnique');
    $estLycee = !$estTechnique && str_starts_with($ordre, 'secondaire');
    $nomEcole = match (true) {
        $estFondamental && !empty($ecole?->nomFondamental) => 'École fondamentale '.$ecole->nomFondamental,
        $estLycee && !empty($ecole?->nomLycee) => 'Lycée '.$ecole->nomLycee,
        $estTechnique && !empty($ecole?->nomProfessionnel) => 'École professionnelle '.$ecole->nomProfessionnel,
        default => $ecole?->nomComplexe ?: ($ecole?->nomEcole ?? ''),
    };
    $complexe = strtolower((string) ($ecole?->typeEcole ?? '')) === 'complexe scolaire' && !empty($ecole?->nomComplexe) && $nomEcole !== $ecole->nomComplexe
        ? 'Complexe scolaire '.$ecole->nomComplexe : null;
    $contact = collect([$ecole?->adresse, $ecole?->telephone ? 'Tél. '.$ecole->telephone : null])->filter()->implode('  ·  ');

    $logo = null;
    if (!empty($ecole?->logoEcole) && is_file($fichierLogo = public_path(ltrim($ecole->logoEcole, '/')))) {
        $logo = $fichierLogo;
    }
    $lignes = fn ($texte) => collect(preg_split('/\R/', (string) $texte))->map(fn ($l) => trim($l))->filter()->values()->all();
    $nomAcademie = \App\Support\NomAcademie::sansPrefixe($ecole?->academieRef?->nom_academie ?? $ecole?->academie ?? '');
    $nomCap = \App\Support\NomAcademie::capSansPrefixe($ecole?->capRef?->nom_cap ?? $ecole?->cap ?? '');
    $officielGauche = array_values(array_filter(array_merge(
        $lignes($ecole?->pays?->entete_document_gauche),
        [$nomAcademie !== '' ? __('bulletins.academie_de', ['name' => $nomAcademie]) : null],
        [$nomCap !== '' && $estFondamental ? __('bulletins.cap_de', ['name' => $nomCap]) : null],
    )));
    $officielDroite = $lignes($ecole?->pays?->entete_document_droite);

    // ---- Classe : informations du bandeau.
    $cycle = trim(preg_replace('/\s*\(.*\)\s*$/', '', \App\Support\ExamenNational::ordresLabels($ecole)[$ordre] ?? ''));
    $filiere = $selectedClasse->filiere?->nom_filiere;
    $effectif = \Illuminate\Support\Facades\DB::table('eleve')->where('id_classe', $selectedClasse->id_classe)
        ->where('id_annee', $selectedAnnee->id_anneeScolaire)->where('etat_dossier', 0)->count();
    $infosClasse = array_filter([
        $t('Classe', 'Class') => $m['entete'] === 'officiel' ? $selectedClasse->nom_classe : null,
        $t('Cycle', 'Level') => $cycle ?: null,
        $t('Série', 'Stream') => $selectedClasse->serie ?: null,
        $t('Filière', 'Programme') => $filiere ?: null,
        $t('Année scolaire', 'School year') => $m['entete'] === 'officiel' ? $selectedAnnee->annee : null,
        $t('Effectif', 'Pupils') => $effectif ?: null,
    ]);

    // ---- Grille : lignes de la première à la dernière occupée ; samedi seulement s'il a des cours.
    $occupee = fn ($heure) => collect($jours)->contains(fn ($j) => !empty($placement[$j][$heure] ?? null));
    $cles = array_keys($heures);
    $premiere = collect($cles)->search(fn ($h) => $occupee($h));
    $derniere = collect($cles)->reverse()->search(fn ($h) => $occupee($h));
    $lignesGrille = $premiere === false ? [] : array_slice($cles, $premiere, $derniere - $premiere + 1);
    $joursAffiches = array_values(array_filter($jours, fn ($j) => $j !== 'Samedi' || collect($lignesGrille)->contains(fn ($h) => !empty($placement[$j][$h] ?? null))));
    $nbPauses = collect($lignesGrille)->filter(fn ($h, $i) => (int) ($recesses[$h] ?? 0) > 0 && $i < count($lignesGrille) - 1)->count();
    // La grille occupe la page : place restante une fois l'en-tête posé ; journées chargées : lignes plus serrées.
    $placeGrille = 90 - ($logo && $m['entete'] === 'officiel' ? 8 : 0) - (count($officielGauche) > 3 ? 3 : 0) - ($bilingue ? 6 : 0);
    $hauteurLigne = count($lignesGrille) ? max(7.5, min(20, ($placeGrille - $nbPauses * 7) / count($lignesGrille))) : 14;

    // Une couleur douce par matière (modèles « couleurs »), dans l'ordre des matières : deux matières n'ont pas la même.
    $palette = [
        ['#e0f2fe', '#0369a1'], ['#dcfce7', '#15803d'], ['#fef3c7', '#b45309'], ['#fce7f3', '#be185d'],
        ['#ede9fe', '#6d28d9'], ['#ffedd5', '#c2410c'], ['#ccfbf1', '#0f766e'], ['#fee2e2', '#b91c1c'],
        ['#e0e7ff', '#4338ca'], ['#f1f5f9', '#334155'], ['#ecfccb', '#4d7c0f'], ['#fae8ff', '#a21caf'],
    ];
    $ordreMatieres = collect($placement)->flatten(1)->filter()->pluck('id_matiere')->unique()->sort()->values()->flip();
    $couleur = fn ($cours) => $palette[((int) ($ordreMatieres[$cours->id_matiere] ?? 0)) % count($palette)];

    $signataire = \App\Support\PresentationBulletin::signataire($ecole ?? (object) [], $ordre);
    $signataireEn = ['Le Proviseur' => 'The Principal', 'Le Directeur des Études' => 'Director of Studies'][$signataire] ?? 'The Head Teacher';
    $pauseSoirFond = $enCouleur ? \App\Support\ModelesCarte::eclaircir($accent, 0.86) : '#e2e2e2';
    if (!$enCouleur) {
        // Étiquettes de matières : blanches, filet noir.
        $couleur = fn ($cours) => ['#ffffff', '#111111'];
    }
    $gris = fn (string $couleur, string $remplacement) => $enCouleur ? $couleur : $remplacement;

    // ---- Décors (dessins SVG) des modèles Primaire et Technique.
    $svg = fn (string $corps, int $l, int $h) => 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="'.$l.'" height="'.$h.'" viewBox="0 0 '.$l.' '.$h.'">'.$corps.'</svg>');
    $soleil = $svg('<circle cx="50" cy="50" r="22" fill="'.$gris('#facc15', '#c4c4c4').'"/>'.collect(range(0, 330, 30))->map(fn ($a) => sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="'.$gris('#facc15', '#c4c4c4').'" stroke-width="6" stroke-linecap="round"/>', 50 + 30 * cos(deg2rad($a)), 50 + 30 * sin(deg2rad($a)), 50 + 44 * cos(deg2rad($a)), 50 + 44 * sin(deg2rad($a))))->implode(''), 100, 100);
    $crayons = $svg(collect([[$gris('#ef4444', '#9a9a9a'), 10], [$gris('#facc15', '#cfcfcf'), 34], [$gris('#22c55e', '#6f6f6f'), 58], [$gris('#3b82f6', '#b5b5b5'), 82]])->map(fn ($c) => '<rect x="'.$c[1].'" y="18" width="18" height="70" rx="3" fill="'.$c[0].'"/><polygon points="'.$c[1].',18 '.($c[1] + 18).',18 '.($c[1] + 9).',0" fill="#f5d0a9"/>')->implode(''), 110, 90);
    $engrenage = $svg(collect(range(0, 315, 45))->map(fn ($a) => sprintf('<rect x="44" y="4" width="12" height="18" rx="2" fill="%s" transform="rotate(%d 50 50)"/>', $gris($accent, '#8a8a8a'), $a))->implode('').'<circle cx="50" cy="50" r="32" fill="'.$gris($accent, '#8a8a8a').'"/><circle cx="50" cy="50" r="13" fill="#ffffff"/>', 100, 100);
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $t('Emploi du temps', 'Timetable') }} — {{ $selectedClasse->nom_classe }}</title>
    <style>
        @page { margin: 8mm 10mm 7mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 8pt; }

        /* En-tête officiel (ministère / république) */
        .entete { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: top; padding: 0; }
        .entete .officiel { width: 40%; text-align: center; font-size: 6.6pt; line-height: 1.35; text-transform: uppercase; color: #374151; }
        .entete .officiel .fort { font-weight: bold; color: #111827; }
        .entete .logo { width: 20%; text-align: center; vertical-align: middle; }
        .entete .logo img { width: 17mm; height: 17mm; }
        .ecole { text-align: center; margin-top: 1.5mm; }
        .ecole .nom { font-size: 13pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3mm; color: {{ $accent }}; }
        .ecole .complexe, .ecole .contact { font-size: 7.2pt; color: #4b5563; margin-top: 0.6mm; }
        .drapeau { display: inline-block; width: 6mm; height: 3.6mm; vertical-align: middle; margin-top: 0.8mm; }
        .drapeau span { display: inline-block; width: 2mm; height: 3.6mm; }

        /* En-tête « logo » : école à gauche, année et classe à droite */
        .officiel-ligne { width: 100%; border-collapse: collapse; font-size: 6pt; text-transform: uppercase; color: #6b7280; }
        .officiel-ligne td { padding: 0 0 1.2mm; }
        .tete-logo { width: 100%; border-collapse: collapse; }
        .tete-logo td { vertical-align: middle; padding: 0; }
        .tete-logo .img { width: 21mm; }
        .tete-logo .img img { width: 18mm; height: 18mm; }
        .tete-logo .nom { font-size: 15pt; font-weight: bold; text-transform: uppercase; color: {{ $accent }}; line-height: 1.1; }
        .tete-logo .sous { font-size: 7.4pt; color: #4b5563; margin-top: 0.8mm; }
        .tete-logo .droite { text-align: right; width: 78mm; }
        .tete-logo .annee { font-size: 9pt; font-weight: bold; color: #111827; }
        .tete-logo .annee small { display: block; font-size: 6.4pt; font-weight: normal; text-transform: uppercase; color: #6b7280; letter-spacing: 0.2mm; }
        .tete-logo .pastille { display: inline-block; white-space: nowrap; margin-top: 1.5mm; padding: 1.4mm 5mm; border-radius: 3mm; background: {{ $accent }}; color: #ffffff; font-weight: bold; font-size: 10pt; }
        .tete-logo .decor img { width: 17mm; height: 17mm; }

        /* Titre et informations de la classe, centrés */
        .titre { text-align: center; margin-top: 2.6mm; }
        .titre .doc { display: inline-block; font-size: 15pt; font-weight: bold; letter-spacing: 1.2mm; color: #ffffff; background: {{ $accent }}; padding: 1.6mm 9mm; border-radius: 1.5mm; }
        .titre.large .doc { display: block; letter-spacing: 1mm; border-radius: 2mm; padding: 1.8mm 0; }
        .infos { width: auto; margin: 2.4mm auto 0; border-collapse: separate; border-spacing: 2mm 0; }
        .infos td { border: 0.3mm solid #cbd5e1; border-radius: 1.5mm; padding: 1.2mm 3.6mm; text-align: center; background: #f8fafc; }
        .infos .lib { display: block; font-size: 5.6pt; text-transform: uppercase; letter-spacing: 0.25mm; color: #64748b; }
        .infos .val { display: block; font-size: 9pt; font-weight: bold; color: #111827; margin-top: 0.3mm; }

        /* Grille */
        .grille { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 3mm; }
        .grille th { background: {{ $accent }}; color: #ffffff; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3mm; padding: 2mm 1mm; border: 0.3mm solid {{ $accent }}; }
        .grille th small { display: block; font-size: 6.2pt; font-weight: normal; letter-spacing: 0; opacity: .85; }
        .grille th.heures { width: 22mm; }
        .grille td { border: 0.3mm solid #cbd5e1; text-align: center; vertical-align: middle; padding: 1mm; }
        .grille tr.pair td.case { background: #fafafa; }
        .grille td.heure { background: {{ $clair }}; font-weight: bold; color: {{ $accent }}; font-size: 8.4pt; line-height: 1.25; }
        .grille td.heure .fin { display: block; font-weight: normal; color: #64748b; font-size: 7.2pt; }
        .cours { border-radius: 1.2mm; padding: 1.2mm 1mm; border-left: 1mm solid #94a3b8; text-align: left; }
        .cours .matiere { font-weight: bold; font-size: 8.6pt; line-height: 1.15; }
        .cours .prof { font-size: 6.8pt; color: #475569; margin-top: 0.5mm; }
        .simple .matiere { font-weight: bold; font-size: 8.8pt; color: #111827; line-height: 1.15; }
        .simple .prof { font-size: 6.6pt; color: #6b7280; margin-top: 0.5mm; }
        .pause td { height: 6.5mm; padding: 0.6mm; font-weight: bold; font-size: 7.4pt; letter-spacing: 0.5mm; text-transform: uppercase; }
        .pause.recre td { background: {{ $clair }}; color: {{ $accent }}; border-color: {{ $clair }}; }
        .pause.soir td { background: {{ $pauseSoirFond }}; color: {{ $accent }}; border-color: {{ $pauseSoirFond }}; }
        .vide-grille { text-align: center; color: #64748b; padding: 12mm 0; font-size: 10pt; }

        /* Pied de page */
        .pied { width: 100%; margin-top: 3mm; border-collapse: collapse; }
        .pied td { vertical-align: top; font-size: 7.4pt; color: #4b5563; }
        .pied .signature { text-align: center; width: 60mm; height: 16mm; color: #111827; font-weight: bold; text-decoration: underline; }
        .pied .decor img { width: 22mm; }
        @if(!$enCouleur)
        /* Noir et blanc : titres et en-têtes encadrés plutôt que remplis (lisible en photocopie, peu d'encre). */
        .titre .doc { background: #ffffff; color: #111111; border: 0.6mm solid #111111; }
        .grille th { background: #e6e6e6; color: #111111; border: 0.3mm solid #111111; }
        .grille td { border-color: #6b6b6b; }
        .grille td.heure .fin { color: #444444; }
        .tete-logo .pastille { background: #ffffff; color: #111111; border: 0.4mm solid #111111; }
        .pause td { border-color: #6b6b6b !important; }
        .cours { border: 0.2mm solid #9a9a9a; border-left: 1mm solid #111111; }
        .cours .prof, .simple .prof { color: #444444; }
        .infos td { background: #ffffff; border-color: #6b6b6b; }
        @endif
    </style>
</head>
<body>
    @if($m['entete'] === 'officiel')
        @if($officielGauche || $officielDroite || $logo)
            <table class="entete">
                <tr>
                    <td class="officiel">@foreach($officielGauche as $l)<div class="{{ $loop->first ? 'fort' : '' }}">{{ $l }}</div>@endforeach</td>
                    <td class="logo">@if($logo)<img src="{{ $logo }}" alt="">@endif</td>
                    <td class="officiel">@foreach($officielDroite as $l)<div class="{{ $loop->first ? 'fort' : '' }}">{{ $l }}</div>@endforeach</td>
                </tr>
            </table>
        @endif
        <div class="ecole">
            <div class="nom">{{ $nomEcole }}</div>
            @if($complexe)<div class="complexe">{{ $complexe }}</div>@endif
            @if($contact)<div class="contact">{{ $contact }}</div>@endif
        </div>
        <div class="titre"><span class="doc">{{ $t('EMPLOI DU TEMPS', 'TIMETABLE') }}</span></div>
    @else
        @if($officielGauche || $officielDroite)
            <table class="officiel-ligne">
                <tr>
                    <td>{{ implode(' · ', array_slice($officielGauche, 0, 2)) }}</td>
                    <td style="text-align:right">{{ implode(' · ', $officielDroite) }}</td>
                </tr>
            </table>
        @endif
        <table class="tete-logo">
            <tr>
                @if($logo)<td class="img"><img src="{{ $logo }}" alt=""></td>@endif
                <td>
                    <div class="nom">{{ $nomEcole }}</div>
                    @if($complexe)<div class="sous">{{ $complexe }}</div>@endif
                    @if($contact)<div class="sous">{{ $contact }}</div>@endif
                </td>
                @if($decor === 'primaire')<td class="decor" style="width:20mm"><img src="{{ $soleil }}" alt=""></td>@endif
                @if($decor === 'technique')<td class="decor" style="width:20mm"><img src="{{ $engrenage }}" alt=""></td>@endif
                <td class="droite">
                    <div class="annee"><small>{{ $t('Année scolaire', 'School year') }}</small>{{ $selectedAnnee->annee }}</div>
                    <span class="pastille">{{ $t('Classe', 'Class') }} : {{ $selectedClasse->nom_classe }}</span>
                </td>
            </tr>
        </table>
        <div class="titre large"><span class="doc">{{ $t('EMPLOI DU TEMPS', 'TIMETABLE') }}</span></div>
    @endif

    @if($infosClasse)
        <table class="infos">
            <tr>
                @foreach($infosClasse as $lib => $val)
                    <td><span class="lib">{{ $lib }}</span><span class="val">{{ $val }}</span></td>
                @endforeach
            </tr>
        </table>
    @endif

    @if(empty($lignesGrille))
        <div class="vide-grille">{{ $t('Aucun cours enregistré pour cette classe.', 'No lessons recorded for this class.') }}</div>
    @else
        <table class="grille">
            <thead>
                <tr>
                    <th class="heures">{{ $bilingue ? 'Horaire' : 'Horaire' }}@if($bilingue)<small>Time</small>@endif</th>
                    @foreach($joursAffiches as $jour)<th>{{ $jour }}@if($bilingue)<small>{{ $joursEn[$jour] }}</small>@endif</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($lignesGrille as $heure)
                    @php
                        $debut = $heures[$heure]['debut'] ?? $heure;
                        $fin = $heures[$heure]['fin'] ?? date('H:i', strtotime($debut.' +1 hour'));
                        $pause = !$loop->last ? (int) ($recesses[$heure] ?? 0) : 0;
                    @endphp
                    <tr class="{{ $loop->even ? 'pair' : '' }}">
                        <td class="heure" style="height: {{ $hauteurLigne }}mm;">{{ $debut }}<span class="fin">{{ $fin }}</span></td>
                        @foreach($joursAffiches as $jour)
                            @php $cours = $placement[$jour][$heure] ?? null; @endphp
                            <td class="case">
                                @if($cours)
                                    @php $nomMatiere = \App\Support\ArabePdf::texte($cours->matiere->nom_matiere ?? 'Matière'); @endphp
                                    @if($m['cellules'] === 'couleurs')
                                        @php [$fond, $trait] = $couleur($cours); @endphp
                                        <div class="cours" style="background: {{ $fond }}; border-left-color: {{ $trait }};">
                                            <div class="matiere" style="color: {{ $trait }};">{{ $nomMatiere }}</div>
                                            @if($cours->enseignant?->nom_prenom_enseignant)<div class="prof">{{ $cours->enseignant->nom_prenom_enseignant }}</div>@endif
                                        </div>
                                    @else
                                        <div class="simple">
                                            <div class="matiere">{{ $nomMatiere }}</div>
                                            @if($cours->enseignant?->nom_prenom_enseignant)<div class="prof">{{ $cours->enseignant->nom_prenom_enseignant }}</div>@endif
                                        </div>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                    @if($pause > 0)
                        @php
                            $reprise = date('H:i', strtotime($fin.' +'.$pause.' minutes'));
                            $soir = \App\Support\GrilleEmploiDuTemps::estPauseSoir($pause);
                        @endphp
                        <tr class="pause {{ $soir ? 'soir' : 'recre' }}">
                            <td>{{ $fin }} – {{ $reprise }}</td>
                            <td colspan="{{ count($joursAffiches) }}">
                                @if($soir)
                                    {{ $t('Pause · le soir reprend à '.$reprise, 'Lunch break · afternoon resumes at '.$reprise) }}
                                @else
                                    {{ $t('Récréation · '.$pause.' min', 'Break') }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="pied">
        <tr>
            @if($decor === 'primaire')<td class="decor" style="width:26mm"><img src="{{ $crayons }}" alt=""></td>@endif
            <td>
                {{ $t('Fait à ……………………, le ……………………', 'Done at ……………………, on ……………………') }}
                <div style="margin-top:1mm; font-size:6.4pt; color:#9ca3af;">{{ $t('Édité le', 'Printed on') }} {{ now()->format('d/m/Y') }}</div>
            </td>
            <td class="signature">{{ $bilingue ? $signataire.' / '.$signataireEn : $signataire }}</td>
        </tr>
    </table>
</body>
</html>
