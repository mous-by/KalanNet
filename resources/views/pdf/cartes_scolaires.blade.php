@use('App\Support\ArabePdf')
@use('App\Support\DecorCarte')
@use('App\Support\ModelesCarte')
@php
    $modele = ModelesCarte::normaliser($config['template']);
    $c1 = $config['primary_color'];
    $c2 = $config['secondary_color'];
    $rectoVerso = ModelesCarte::rectoVerso($modele);
    $filigrane = $config['show_mali_flag'] && $config['mali_flag_style'] === 'watermark';
    $drapeau = $config['show_mali_flag'] && $config['mali_flag_style'] !== 'watermark';
    $decor = $rectoVerso ? DecorCarte::vertical(false, $c1, $c2, $filigrane) : DecorCarte::recto($modele, $c1, $c2, $filigrane);
    $decorVerso = $rectoVerso ? DecorCarte::vertical(true, $c1, $c2) : null;

    // En-tête officiel du pays (ex. « RÉPUBLIQUE DU MALI · Un Peuple - Un But - Une Foi ») sur une ligne.
    $entete = collect(preg_split('/\R/', (string) ($ecole?->pays?->entete_document_droite ?? '')))->map(fn ($l) => trim($l))->filter()->implode(' · ');
    $nomEcole = ArabePdf::texte($schoolName);
    $titre = $config['card_title'];
    $logo = $config['show_school_logo'] ? $schoolLogoPath : null;
    $filiere = $classe->filiere?->nom_filiere ?? null;
    $serie = $classe->serie ?? null;
    $telephoneEcole = $adminPhone ?: ($ecole?->telephone ?: null);

    // Pages : 10 cartes (2 × 5) en A4 portrait. Recto / verso : 9 cartes (3 × 3) par page,
    // la page des rectos puis celle des versos, en miroir pour l'impression recto verso.
    $parPage = $rectoVerso ? 9 : 10;
    $pages = $eleves->values()->chunk($parPage);
@endphp
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: {{ $rectoVerso ? '0' : '5mm 12mm' }}; }
        /* Recto / verso : cartes placées au millimètre, grille centrée (mêmes marges des deux côtés). */
        .feuille { position: relative; width: 210mm; height: 296mm; overflow: hidden; }
        .feuille .place { position: absolute; }
        .feuille .consigne { position: absolute; left: 0; width: 210mm; bottom: 4mm; text-align: center; font-size: 6.5pt; color: #9ca3af; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #111827; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        table.grille { border-collapse: separate; border-spacing: 0; }
        table.grille td { padding: 0; vertical-align: top; }

        .carte { position: relative; overflow: hidden; width: 85.6mm; height: 54mm; border-radius: 3mm; border: 0.2mm solid #d1d5db; }
        .carte.verticale { width: 54mm; height: 85.6mm; }
        .carte .fond { position: absolute; left: 0; top: 0; width: 85.6mm; height: 54mm; }
        .carte.verticale .fond { width: 54mm; height: 85.6mm; }
        .abs { position: absolute; }

        .tete { left: 3mm; top: 2.2mm; width: 79.6mm; height: 13.4mm; overflow: hidden; }
        .tete .logo { position: absolute; left: 0; top: 0.4mm; width: 10.5mm; height: 10.5mm; }
        .tete .textes { position: absolute; left: 12.5mm; top: 0; width: 63mm; }
        .tete.sans-logo .textes { left: 0; width: 76mm; }
        .entete { font-size: 4.3pt; line-height: 1.15; color: #374151; text-transform: uppercase; }
        .nom-ecole { font-size: 7pt; line-height: 1.12; font-weight: bold; text-transform: uppercase; color: {{ $c1 }}; margin-top: 0.4mm; }
        .titre { font-size: 5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.2mm; margin-top: 0.5mm; color: #111827; }
        .drapeau { display: inline-block; width: 4.8mm; height: 3mm; vertical-align: middle; margin-right: 1mm; border: 0.1mm solid rgba(0,0,0,.2); }
        .drapeau span { display: inline-block; width: 1.6mm; height: 3mm; }
        .vert { background: #14b53a; } .or { background: #fcd116; } .rouge { background: #ce1126; }

        .photo { left: 4mm; top: 17mm; width: 17.5mm; height: 21.5mm; border: 0.6mm solid #ffffff; border-radius: 1.5mm; background: #e5e7eb; overflow: hidden; text-align: center; }
        .photo img { width: 100%; height: 100%; }
        .photo .vide { font-size: 5pt; color: #6b7280; padding-top: 8.5mm; }
        .infos { left: 24mm; top: 16.6mm; width: 43mm; height: 24mm; overflow: hidden; font-size: 5.7pt; line-height: 1.3; }
        .infos .nom { font-size: 8.5pt; font-weight: bold; text-transform: uppercase; line-height: 1.1; }
        .infos .prenom { font-size: 7.5pt; font-weight: bold; line-height: 1.15; margin-bottom: 0.5mm; }
        .infos .lib { color: #4b5563; }
        .qr { right: 3.5mm; top: 17mm; width: 14mm; height: 14mm; background: #fff; padding: 0.5mm; border-radius: 1mm; }
        .qr img { width: 13mm; height: 13mm; }
        .codebarres { left: 4mm; top: 41.8mm; width: 36mm; background: #fff; border-radius: 1mm; padding: 0.6mm 0.8mm 0.3mm; text-align: center; }
        .codebarres img { width: 34.4mm; height: 6.2mm; }
        .codebarres div { font-size: 4.6pt; letter-spacing: 0.15mm; line-height: 1.1; }
        .signature { right: 3.5mm; top: 39.5mm; width: 24mm; text-align: center; font-size: 5pt; color: #374151; }
        .signature img { max-width: 22mm; max-height: 8mm; }

        /* Élégant : bleu nuit et or. */
        .m-elegant .entete, .m-elegant .titre { color: {{ ModelesCarte::eclaircir($c2, 0.35) }}; }
        .m-elegant .nom-ecole { color: {{ $c2 }}; }
        .m-elegant .bandeau { left: 4mm; top: 48.6mm; font-size: 6.5pt; font-weight: bold; letter-spacing: 0.25mm; color: {{ $c2 }}; }
        .m-elegant .signature { top: 33.5mm; }
        .m-elegant .photo { border-color: {{ $c2 }}; }

        /* Franco-arabe : bandeau clair, panneau vert. */
        .m-franco_arabe .tete { text-align: center; }
        .m-franco_arabe .arabe { font-size: 6pt; color: {{ $c1 }}; text-transform: none; letter-spacing: 0; }
        .m-franco_arabe .infos, .m-franco_arabe .infos .lib { color: #ffffff; }
        .m-franco_arabe .signature { color: #ffffff; }
        .m-franco_arabe .signature .encre { background: #fff; border-radius: 1mm; padding: 0.3mm; }
        .m-franco_arabe .photo { top: 19mm; height: 20.5mm; }
        .m-franco_arabe .infos { top: 19mm; height: 21.5mm; }
        .m-franco_arabe .qr { top: 19.5mm; }
        .m-franco_arabe .codebarres { top: 41.6mm; }
        .m-franco_arabe .signature { top: 41mm; }

        /* École de santé : en-tête à gauche, tracé à droite. */
        .m-sante .tete .textes { width: 47mm; }
        .m-sante .entete { font-size: 3.8pt; }
        .m-sante .nom-ecole { font-size: 6.6pt; }

        /* Primaire : couleurs vives, signature à l'écart des crayons. */
        .m-primaire .nom-ecole { color: {{ $c2 }}; }
        .m-primaire .titre { color: {{ $c1 }}; }
        .m-primaire .tete .textes, .m-primaire .tete.sans-logo .textes { left: 16mm; width: 52mm; }
        .m-primaire .signature { right: 17mm; top: 40.5mm; }
        .m-primaire .photo { border-color: {{ $c1 }}; }

        /* Recto / verso (carte verticale). */
        .v .tete { left: 3mm; top: 2.5mm; width: 48mm; height: 17mm; }
        .v .tete .logo { width: 9mm; height: 9mm; }
        .v .tete .textes { left: 10.5mm; width: 37.5mm; }
        .v .tete.sans-logo .textes { left: 0; width: 48mm; }
        .v .entete, .v .titre { color: {{ ModelesCarte::eclaircir($c1, 0.8) }}; }
        .v .nom-ecole { color: #ffffff; font-size: 6.4pt; }
        .v .photo { left: 4mm; top: 24.5mm; width: 18mm; height: 22mm; border-color: {{ $c1 }}; }
        .v .infos { left: 24mm; top: 25mm; width: 27mm; height: 22mm; font-size: 5.6pt; }
        .v .infos .nom { font-size: 7.5pt; }
        .v .infos .prenom { font-size: 6.8pt; }
        .v .qr { right: 4mm; left: auto; top: 49.5mm; }
        .v .signature { left: 4mm; right: auto; top: 51mm; width: 26mm; }
        .v .codebarres { left: 4mm; top: 65.5mm; width: 46mm; }
        .v .codebarres img { width: 44.4mm; }
        .verso .titre-verso { left: 4mm; top: 17.5mm; font-size: 6.4pt; font-weight: bold; text-transform: uppercase; color: {{ $c1 }}; letter-spacing: 0.2mm; }
        .verso .lignes { left: 4mm; top: 23mm; width: 46mm; font-size: 5.6pt; line-height: 1.3; }
        .verso .lignes .lib { display: block; color: #6b7280; font-size: 4.8pt; text-transform: uppercase; margin-top: 1.2mm; }
        .verso .lignes .val { display: block; font-weight: bold; }
        .verso .avis { left: 4mm; top: 63mm; width: 30mm; font-size: 4.6pt; line-height: 1.25; color: #374151; font-style: italic; }
        .verso .qr { right: 4mm; top: 61.5mm; }
        .verso .tete .nom-ecole { margin-top: 1mm; }
    </style>
</head>
<body>
@if($rectoVerso)
    @php
        // 3 colonnes de 54 mm et 3 rangées de 85,6 mm, espacées de 6 mm, centrées sur la feuille A4.
        $gauche = (210 - (3 * 54 + 2 * 6)) / 2;
        $haut = (297 - (3 * 85.6 + 2 * 6)) / 2;
    @endphp
    @foreach($pages as $page)
        @foreach([false, true] as $verso)
            <div class="page feuille">
                @foreach($page->values() as $i => $eleve)
                    @php
                        $photoPath = $eleve->image ? public_path($eleve->image) : null;
                        $hasPhoto = $photoPath && is_file($photoPath);
                        $qrCode = $qrCodes[$eleve->id_eleve] ?? null;
                        $naissance = $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : null;
                        $matricule = $eleve->matricule ?: null;
                        $parent = $eleve->relationLoaded('parents') ? $eleve->parents->first() : null;
                        // Feuille retournée sur le bord long : la colonne d'un verso est l'inverse de celle de son recto.
                        $colonne = $verso ? 2 - $i % 3 : $i % 3;
                        $rangee = intdiv($i, 3);
                    @endphp
                    <div class="place" style="left: {{ $gauche + $colonne * 60 }}mm; top: {{ $haut + $rangee * 91.6 }}mm;">
                        @include('pdf.cartes.verticale', ['verso' => $verso])
                    </div>
                @endforeach
                <div class="consigne">{{ $verso ? 'Versos — page '.($loop->parent->iteration * 2).' : à imprimer au dos de la page précédente' : 'Rectos — page '.($loop->parent->iteration * 2 - 1).' : imprimez en recto verso, retournement sur le bord long' }}</div>
            </div>
        @endforeach
    @endforeach
@else
@foreach($pages as $page)
    <div class="page">
        <table class="grille">
            @foreach($page->chunk(2) as $rang)
                <tr>
                    @foreach($rang as $eleve)
                        @php
                            $photoPath = $eleve->image ? public_path($eleve->image) : null;
                            $hasPhoto = $photoPath && is_file($photoPath);
                            $qrCode = $qrCodes[$eleve->id_eleve] ?? null;
                            $naissance = $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : null;
                            $matricule = $eleve->matricule ?: null;
                            $parent = $eleve->relationLoaded('parents') ? $eleve->parents->first() : null;
                            $espace = $loop->first ? '' : 'padding-left:4mm';
                        @endphp
                        <td style="{{ $espace }}; padding-bottom: 2.5mm;">
                            @include('pdf.cartes.horizontale')
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
@endif
</body>
</html>
