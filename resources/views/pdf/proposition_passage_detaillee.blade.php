<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Propositions de passage</title>
    <style>
        @page { margin: 14px 14px 16px; }
        body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 10px; line-height: 1.2; }
        table { border-collapse: collapse; }
        .tutelle { font-style: italic; font-size: 10.5px; line-height: 1.1; }
        .tutelle .e { padding-left: 60px; font-size: 8px; }
        .titre { text-align: center; font-weight: bold; text-decoration: underline; font-size: 13px; margin-top: 4px; }
        .bande { background: #bfbfbf; text-align: center; font-weight: bold; font-style: italic; width: 34%; margin: 1px auto; padding: 1px; font-size: 12px; }
        .infos { width: 100%; font-size: 10px; margin: 2px 0 5px; }
        .infos td { vertical-align: top; }
        .gras { font-weight: bold; text-decoration: underline; }
        .case { display: inline-block; width: 24px; height: 11px; border: 1px solid #000; text-align: center; line-height: 11px; font-weight: bold; }
        .liste { width: 100%; table-layout: fixed; }
        .liste thead { display: table-header-group; }
        .liste .h { border: 1px solid #000; font-weight: bold; text-align: center; padding: 1px; font-size: 8.5px; }
        .liste td.c { border: 1px solid #000; padding: 1.5px; word-wrap: break-word; }
        .fille td.c { font-weight: bold; }
        .ctr { text-align: center; }
        .nb { font-size: 9.5px; font-weight: bold; margin-top: 6px; page-break-inside: avoid; }
        .nb .cadre { border: 1px solid #000; width: 62%; padding: 2px; margin-top: 1px; }
        .bas { page-break-inside: avoid; margin-top: 8px; font-size: 10.5px; }
        .bas .titre2 { font-weight: bold; text-decoration: underline; font-size: 11px; margin-bottom: 2px; }
        .recap { width: 34%; border: 1px solid #000; }
        .recap td { border: 1px solid #000; padding: 1.5px 4px; text-align: center; }
        .recap td:first-child { text-align: left; font-weight: bold; font-size: 10px; }
        .recap .t td { background: #00e000; font-weight: bold; font-style: italic; text-align: center; }
        .pointilles { text-align: center; font-weight: bold; margin: 6px 0; letter-spacing: 1px; }
        .signature { width: 100%; margin-top: 3px; }
        .signature td { vertical-align: top; }
    </style>
</head>
<body>
    @php
        $C = \App\Support\PropositionPassageDonnees::class;
        $n = 0;
        $coche = fn (bool $oui) => '<span class="case">'.($oui ? 'X' : '').'</span>';
        $tableau = function (?array $r, ?string $classe = null) {
            $lignes = ['total' => 'EFFECTIF', 'passe' => 'PASSANTS', 'redouble' => 'REDOUBLANTS', 'exclu' => 'EXCLUS'];
            if ($r && $r['abandon']['t'] > 0) {
                $lignes['abandon'] = 'ABANDONS';
            }
            $h = '<table class="recap">'.($classe ? '<tr><td></td><td colspan="2" style="background: #bfbfbf; font-weight: bold; font-style: italic;">'.e($classe).'</td><td></td></tr>' : '');
            $h .= '<tr class="t"><td></td><td>Garçons</td><td>Filles</td><td>Total</td></tr>';
            foreach ($lignes as $k => $l) {
                $h .= '<tr><td>'.$l.'</td>'.collect(['g', 'f', 't'])->map(fn ($c) => '<td>'.($r ? str_pad((string) $r[$k][$c], 2, '0', STR_PAD_LEFT) : '').'</td>')->implode('').'</tr>';
            }

            return $h.'</table>';
        };
    @endphp

    <div class="tutelle">
        {{ $e['gauche'] }}<div class="e">**********</div>
        Direction Nationale de l'Enseignement Fondamental<div class="e">**********</div>
        Académie d'Enseignement de {{ $e['academie'] }}<div class="e">**********</div>
        Centre d'Animation Pédagogique de {{ $e['cap'] }}
    </div>
    <div class="titre">PROPOSITIONS DE PASSAGE, DE REDOUBLEMENT ET D'EXCLUSION</div>
    <div class="bande">{{ str_replace('-', '/', $e['annee']) }}</div>
    <table class="infos">
        <tr>
            <td style="width: 33%;">
                <div class="gras">RENSEIGNEMENTS GÉNÉRAUX</div>
                <div class="gras">ÉCOLE FONDAMENTALE</div>
                <div class="gras">COMMUNE {{ $r['commune'] ?? '' }}</div>
                <div class="gras">STATUT DE L'ÉCOLE :</div>
                @foreach($C::STATUTS_ECOLE as $v => $libelle)
                    <div style="margin-top: 1px;">{!! $coche(($r['statut'] ?? null) === $v) !!} *{{ $libelle }}</div>
                @endforeach
            </td>
            <td style="width: 34%; text-align: center;">
                <div class="bande" style="width: 100%;">{{ $e['ecole'] }}</div>
                <div class="gras" style="text-decoration: underline;">N° Téléphone : {{ $r['telephone'] ?? '' }}</div>
                <div style="height: 22px;"></div>
                <span class="gras">COURS :</span>
                <div class="bande" style="width: 100%;">{{ $e['classe'] }}</div>
            </td>
            <td style="width: 33%; padding-left: 50px;">
                <div class="gras">MODE DE RECRUTEMENT</div>
                @foreach($C::RECRUTEMENTS as $v => $libelle)
                    <div style="margin-top: 1px;">{!! $coche(($r['recrutement'] ?? null) === $v) !!} *{{ $libelle }}</div>
                @endforeach
            </td>
        </tr>
    </table>

    <table class="liste">
        <thead>
            <tr>
                @foreach([2.5, 9, 9, 2.5, 2.5, 2.5, 3, 6, 7, 6.5, 7, 6.5, 3.5, 4.5, 3, 3.5, 2.2, 2.2, 2.1, 3.5, 6, 6] as $w)
                    <td style="width: {{ $w }}%; height: 0; padding: 0;"></td>
                @endforeach
            </tr>
            <tr>
                <td class="h" rowspan="2">N°</td><td class="h" rowspan="2">Prénoms</td><td class="h" rowspan="2">Nom</td><td class="h" rowspan="2">Se-xe</td>
                <td class="h" rowspan="2">Jour Nais.</td><td class="h" rowspan="2">Mois Nais.</td><td class="h" rowspan="2">Année Nais.</td><td class="h" rowspan="2">Lieu Naiss.</td>
                <td class="h" colspan="2">Père</td><td class="h" colspan="2">Mère</td>
                <td class="h" rowspan="2">Année de recru-tement</td><td class="h" rowspan="2">Classes doublées au 1er cycle</td><td class="h" rowspan="2">Scola-rité 1er cycle</td><td class="h" rowspan="2">Entrée en 7ème</td>
                <td class="h" colspan="3">Nbre d'années passées par classe</td>
                <td class="h" rowspan="2">Moy. Ann.</td><td class="h" rowspan="2">Avis du Conseil des maîtres</td><td class="h" rowspan="2">Avis du CAP</td>
            </tr>
            <tr>
                <td class="h">Prénom(s)</td><td class="h">Nom</td><td class="h">Prénom(s)</td><td class="h">Nom</td>
                <td class="h">7è</td><td class="h">8è</td><td class="h">9è</td>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $l)
                @php
                    $n++;
                    [$jour, $mois, $an] = array_pad(explode('/', (string) ($l['date_naissance'] ?? '')), 3, '');
                    [$prenomPere, $nomPere] = $C::decouperNom($l['pere'] ?? null);
                    [$prenomMere, $nomMere] = $C::decouperNom($l['mere'] ?? null);
                    $sexe = $l['sexe'] ?? 'M';
                @endphp
                <tr class="{{ $sexe === 'F' ? 'fille' : '' }}">
                    <td class="c ctr">{{ $n }}</td><td class="c">{{ $l['prenom'] }}</td><td class="c">{{ $l['nom'] }}</td><td class="c ctr">{{ $sexe }}</td>
                    <td class="c ctr">{{ $jour }}</td><td class="c ctr">{{ $mois }}</td><td class="c ctr">{{ $an }}</td><td class="c">{{ $l['lieu_naissance'] }}</td>
                    <td class="c">{{ $prenomPere }}</td><td class="c">{{ $nomPere }}</td><td class="c">{{ $prenomMere }}</td><td class="c">{{ $nomMere }}</td>
                    <td class="c ctr">{{ $l['annee_recrutement'] ?? '' }}</td><td class="c ctr">{{ $l['doublees_1er'] ?? '' }}</td><td class="c ctr">{{ $l['scolarite_1er'] ?? '' }}</td><td class="c ctr">{{ $l['annee_7e'] ?? '' }}</td>
                    <td class="c ctr">{{ $l['annees_7'] ?? '' }}</td><td class="c ctr">{{ $l['annees_8'] ?? '' }}</td><td class="c ctr">{{ $l['annees_9'] ?? '' }}</td>
                    <td class="c ctr" style="font-weight: bold;">{{ isset($l['moyenne']) ? number_format((float) $l['moyenne'], 2, '.', '') : '' }}</td>
                    <td class="c" style="font-weight: bold;">{{ $C::avis($l['proposition'] ?? null, $sexe, $e['examen']) }}</td>
                    <td class="c" style="font-weight: bold;">{{ $C::avis($l['decision_cap'] ?? null, $sexe, $e['examen']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="nb">
        NB : Évitez les surcharges et l'utilisation du correcteur.
        <div class="cadre">
            NB : 1_Sont exclus les élèves qui n'ont pas d'enregistrements complets c'est-à-dire sans DOSSIER SCOLAIRE (sans renseignements complets) et ceux qui sont en limite de scolarité ou d'âge.<br>
            2_On rappelle qu'un élève n'a droit qu'à deux (2) redoublements sur des années d'études différentes dans son cycle d'études.<br>
            3_L'élève ne peut doubler qu'une seule fois son année d'études au 2ème cycle exceptée la 9ème d'études s'il n'a jamais doublé avant la 9ème Année.<br>
            4_Les Données du tableau doivent être conformes à celles des dossiers scolaires en état civil et en scolarité.
        </div>
    </div>

    <div class="bas">
        <div class="titre2">PROPOSITIONS DE PASSAGE : TABLEAU RÉCAPITULATIF DU CONSEIL DES MAÎTRES.</div>
        {!! $tableau($recap, $e['classe']) !!}
        <table class="signature">
            <tr>
                <td style="width: 60%; font-weight: bold; text-decoration: underline;"><br>Le Maître Titulaire</td>
                <td style="text-align: center;">{{ $e['lieu'] }}, le {{ $date->format('d/m/Y') }}<br><span class="gras">Le Directeur d'école</span><br><br><br><b>{{ $directeur }}</b></td>
            </tr>
        </table>
        <div class="pointilles">……………………………………………………………………………………………………………</div>
        <div class="titre2">PROPOSITIONS DE PASSAGE : TABLEAU RÉCAPITULATIF DU C.A.P.</div>
        {!! $tableau($recapCap) !!}
        <table class="signature">
            <tr>
                <td style="width: 60%; font-weight: bold;">Vu et Approuvé</td>
                <td style="text-align: center;">{{ $e['localite_cap'] }}, le {{ $fiche?->valide_at?->format('d/m/Y') ?? '……/……/20……' }}<br><span class="gras">LE DIRECTEUR du C.A.P.</span></td>
            </tr>
        </table>
    </div>
</body>
</html>
