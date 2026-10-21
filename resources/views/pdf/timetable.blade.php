@php
    // ---- École : nom selon le cycle (complexe scolaire), logo, en-tête officiel comme les bulletins.
    $ordre = trim((string) ($selectedClasse->ordreEnseignement ?? ''));
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
        'Classe' => $selectedClasse->nom_classe,
        'Cycle' => $cycle ?: null,
        'Série' => $selectedClasse->serie ?: null,
        'Filière' => $filiere ?: null,
        'Année scolaire' => $selectedAnnee->annee,
        'Effectif' => $effectif ? $effectif.' élèves' : null,
    ]);

    // ---- Grille : lignes de la première à la dernière occupée ; samedi seulement s'il a des cours.
    $occupee = fn ($heure) => collect($jours)->contains(fn ($j) => !empty($placement[$j][$heure] ?? null));
    $cles = array_keys($heures);
    $premiere = collect($cles)->search(fn ($h) => $occupee($h));
    $derniere = collect($cles)->reverse()->search(fn ($h) => $occupee($h));
    $lignesGrille = $premiere === false ? [] : array_slice($cles, $premiere, $derniere - $premiere + 1);
    $joursAffiches = array_values(array_filter($jours, fn ($j) => $j !== 'Samedi' || collect($lignesGrille)->contains(fn ($h) => !empty($placement[$j][$h] ?? null))));
    $nbPauses = collect($lignesGrille)->filter(fn ($h, $i) => (int) ($recesses[$h] ?? 0) > 0 && $i < count($lignesGrille) - 1)->count();
    // La grille occupe la page : hauteur des lignes selon leur nombre (entre 11 et 21 mm).
    // Place restante une fois l'en-tête posé (le logo le rend plus haut) ; journées très chargées : lignes plus serrées.
    $placeGrille = 90 - ($logo ? 8 : 0) - (count($officielGauche) > 3 ? 3 : 0);
    $hauteurLigne = count($lignesGrille) ? max(7.5, min(20, ($placeGrille - $nbPauses * 7) / count($lignesGrille))) : 14;

    // Une couleur douce par matière (toujours la même), pour repérer les cours d'un coup d'œil.
    $palette = [
        ['#e0f2fe', '#0369a1'], ['#dcfce7', '#15803d'], ['#fef3c7', '#b45309'], ['#fce7f3', '#be185d'],
        ['#ede9fe', '#6d28d9'], ['#ffedd5', '#c2410c'], ['#ccfbf1', '#0f766e'], ['#fee2e2', '#b91c1c'],
        ['#e0e7ff', '#4338ca'], ['#f1f5f9', '#334155'], ['#ecfccb', '#4d7c0f'], ['#fae8ff', '#a21caf'],
    ];
    // Couleurs données dans l'ordre des matières de l'emploi du temps : deux matières n'ont pas la même (jusqu'à 12).
    $ordreMatieres = collect($placement)->flatten(1)->filter()->pluck('id_matiere')->unique()->sort()->values()->flip();
    $couleur = fn ($cours) => $palette[((int) ($ordreMatieres[$cours->id_matiere] ?? 0)) % count($palette)];

    $signataire = \App\Support\PresentationBulletin::signataire($ecole ?? (object) [], $ordre);
    $accent = '#14365d';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Emploi du temps — {{ $selectedClasse->nom_classe }}</title>
    <style>
        @page { margin: 8mm 10mm 7mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 8pt; }

        /* En-tête officiel */
        .entete { width: 100%; border-collapse: collapse; }
        .entete td { vertical-align: top; padding: 0; }
        .entete .officiel { width: 40%; text-align: center; font-size: 6.6pt; line-height: 1.35; text-transform: uppercase; color: #374151; }
        .entete .officiel .fort { font-weight: bold; color: #111827; }
        .entete .logo { width: 20%; text-align: center; vertical-align: middle; }
        .entete .logo img { width: 17mm; height: 17mm; }
        .ecole { text-align: center; margin-top: 1.5mm; }
        .ecole .nom { font-size: 13pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3mm; color: {{ $accent }}; }
        .ecole .complexe, .ecole .contact { font-size: 7.2pt; color: #4b5563; margin-top: 0.6mm; }

        /* Titre et informations de la classe, centrés */
        .titre { text-align: center; margin-top: 2.6mm; }
        .titre .doc { display: inline-block; font-size: 15pt; font-weight: bold; letter-spacing: 1.2mm; color: #ffffff; background: {{ $accent }}; padding: 1.6mm 9mm; border-radius: 1.5mm; }
        .infos { width: auto; margin: 2.4mm auto 0; border-collapse: separate; border-spacing: 2mm 0; }
        .infos td { border: 0.3mm solid #cbd5e1; border-radius: 1.5mm; padding: 1.2mm 3.6mm; text-align: center; background: #f8fafc; }
        .infos .lib { display: block; font-size: 5.6pt; text-transform: uppercase; letter-spacing: 0.25mm; color: #64748b; }
        .infos .val { display: block; font-size: 9pt; font-weight: bold; color: #111827; margin-top: 0.3mm; }

        /* Grille */
        .grille { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 3.4mm; }
        .grille th { background: {{ $accent }}; color: #ffffff; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.4mm; padding: 2mm 1mm; border: 0.3mm solid {{ $accent }}; }
        .grille th.heures { width: 22mm; }
        .grille td { border: 0.3mm solid #cbd5e1; text-align: center; vertical-align: middle; padding: 1mm; }
        .grille td.heure { background: #f1f5f9; font-weight: bold; color: {{ $accent }}; font-size: 8.4pt; line-height: 1.25; }
        .grille td.heure .fin { display: block; font-weight: normal; color: #64748b; font-size: 7.2pt; }
        .cours { border-radius: 1.2mm; padding: 1.2mm 1mm; border-left: 1mm solid #94a3b8; text-align: left; }
        .cours .matiere { font-weight: bold; font-size: 8.6pt; line-height: 1.15; }
        .cours .prof { font-size: 6.8pt; color: #475569; margin-top: 0.5mm; }
        .pause td { height: 6.5mm; padding: 0.6mm; font-weight: bold; font-size: 7.4pt; letter-spacing: 0.5mm; text-transform: uppercase; }
        .pause.recre td { background: #fffbeb; color: #92400e; border-color: #fde68a; }
        .pause.soir td { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
        .vide-grille { text-align: center; color: #64748b; padding: 12mm 0; font-size: 10pt; }

        /* Pied de page */
        .pied { width: 100%; margin-top: 3mm; border-collapse: collapse; }
        .pied td { vertical-align: top; font-size: 7.2pt; color: #6b7280; }
        .pied .signature { text-align: center; width: 55mm; height: 16mm; color: #111827; font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
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
        @if($ecole?->telephone || $ecole?->adresse)
            <div class="contact">{{ collect([$ecole?->adresse, $ecole?->telephone ? 'Tél. '.$ecole->telephone : null])->filter()->implode('  ·  ') }}</div>
        @endif
    </div>

    <div class="titre"><span class="doc">EMPLOI DU TEMPS</span></div>
    <table class="infos">
        <tr>
            @foreach($infosClasse as $lib => $val)
                <td><span class="lib">{{ $lib }}</span><span class="val">{{ $val }}</span></td>
            @endforeach
        </tr>
    </table>

    @if(empty($lignesGrille))
        <div class="vide-grille">Aucun cours enregistré pour cette classe.</div>
    @else
        <table class="grille">
            <thead>
                <tr>
                    <th class="heures">Horaire</th>
                    @foreach($joursAffiches as $jour)<th>{{ $jour }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($lignesGrille as $heure)
                    @php
                        $debut = $heures[$heure]['debut'] ?? $heure;
                        $fin = $heures[$heure]['fin'] ?? date('H:i', strtotime($debut.' +1 hour'));
                        $pause = !$loop->last ? (int) ($recesses[$heure] ?? 0) : 0;
                    @endphp
                    <tr>
                        <td class="heure" style="height: {{ $hauteurLigne }}mm;">{{ $debut }}<span class="fin">{{ $fin }}</span></td>
                        @foreach($joursAffiches as $jour)
                            @php $cours = $placement[$jour][$heure] ?? null; @endphp
                            <td>
                                @if($cours)
                                    @php [$fond, $trait] = $couleur($cours); @endphp
                                    <div class="cours" style="background: {{ $fond }}; border-left-color: {{ $trait }};">
                                        <div class="matiere" style="color: {{ $trait }};">{{ \App\Support\ArabePdf::texte($cours->matiere->nom_matiere ?? 'Matière') }}</div>
                                        @if($cours->enseignant?->nom_prenom_enseignant)<div class="prof">{{ $cours->enseignant->nom_prenom_enseignant }}</div>@endif
                                    </div>
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
                                {{ $soir ? 'Pause · le soir reprend à '.$reprise : 'Récréation · '.$pause.' min' }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="pied">
        <tr>
            <td>Édité le {{ now()->format('d/m/Y') }}</td>
            <td class="signature">{{ $signataire }}</td>
        </tr>
    </table>
</body>
</html>
