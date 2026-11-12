<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau des notes — {{ $classe->nom_classe }}</title>
    @php
        $nbMatieres = max(1, $matieres->count());
        // Écriture la plus grande possible selon le nombre de matières (en points).
        $t = $nbMatieres <= 8 ? 11 : ($nbMatieres <= 11 ? 10 : ($nbMatieres <= 14 ? 9 : 8.5));
        $hauteurTitres = $t * 11.5;
    @endphp
    <style>
        @page { margin: 20pt 20pt 24pt; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: {{ $t }}pt; }
        .entete { width: 100%; border-collapse: collapse; margin-bottom: 4pt; }
        .entete td { vertical-align: top; font-size: 8pt; line-height: 1.3; }
        .entete .centre { text-align: center; }
        .logo { height: 34pt; }
        h1 { font-size: 14pt; margin: 2pt 0; text-transform: uppercase; letter-spacing: .4pt; }
        .sous { font-size: 11pt; font-weight: bold; }
        table.notes { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .notes th, .notes td { border: 0.6pt solid #333; padding: 3pt 2pt; text-align: center; }
        .notes th { background: #e8e8e8; font-size: {{ $t - 0.5 }}pt; font-weight: bold; line-height: 1.15; }
        .notes th.matiere { height: {{ $hauteurTitres }}pt; padding: 0; vertical-align: bottom; }
        .notes th.matiere div { width: {{ $hauteurTitres }}pt; margin-left: -{{ round($hauteurTitres / 2 - 14) }}pt; margin-bottom: {{ round($hauteurTitres / 2 - 6) }}pt; transform: rotate(-90deg); white-space: nowrap; text-align: left; font-size: {{ $t - 0.5 }}pt; }
        .notes th.coef { background: #f4f4f4; font-weight: normal; font-size: {{ $t - 2 }}pt; padding: 2pt 1pt; }
        .notes td { font-size: {{ $t }}pt; }
        .notes td.nom { text-align: left; white-space: nowrap; overflow: hidden; }
        .notes td.mat { font-size: {{ $t - 1.5 }}pt; white-space: nowrap; overflow: hidden; }
        .notes td.faible { color: #b00020; }
        .notes tr:nth-child(even) td { background: #f6f6f6; }
        .notes .moy { font-weight: bold; background: #eef3ff !important; }
        .notes .rang { font-weight: bold; }
        .notes tfoot td { background: #e8e8e8; font-weight: bold; font-size: {{ $t - 1 }}pt; }
        /* Page des indicateurs : seule sur sa page, grande écriture, facile à lire. */
        .page-indicateurs { page-break-before: always; }
        .page-indicateurs h2 { text-align: center; font-size: 15pt; margin: 4pt 0 2pt; text-transform: uppercase; }
        .page-indicateurs .sous-titre { text-align: center; font-size: 11.5pt; font-weight: bold; margin-bottom: 6pt; }
        .indicateurs { width: 100%; border-collapse: collapse; }
        .indicateurs th, .indicateurs td { border: 0.7pt solid #444; padding: 2.4pt 6pt; text-align: center; font-size: 10pt; }
        .indicateurs th { background: #dde3ea; font-size: 10pt; }
        .indicateurs td.l { text-align: left; font-weight: bold; }
        .indicateurs tr.impair td { background: #f5f7fa; }
        .indicateurs .section td { text-align: left; font-weight: bold; font-size: 10.5pt; background: #c9d6ea; color: #10203a; }
        .indicateurs .ensemble td { font-weight: bold; background: #e7eefb; }
        .indicateurs .ok { color: #11743a; font-weight: bold; }
        .indicateurs .ko { color: #b00020; font-weight: bold; }
        .indicateurs .vide { color: #777; font-style: italic; }
        .bas { width: 100%; margin-top: 6pt; border-collapse: collapse; }
        .bas td { font-size: 9pt; vertical-align: top; }
    </style>
</head>
<body>
    @php
        $seuil = $surDix ? 5 : 10;
        $fmt = fn ($n) => number_format((float) $n, 2, ',', '');
        $largeurMatiere = round(63.5 / $nbMatieres, 2);
        $rangTexte = fn ($r) => $r['rang'].($r['rang'] === 1 ? 'er' : 'e').(($exaequo[$r['rang']] ?? 0) > 1 ? ' ex' : '');
    @endphp

    <table class="entete">
        <tr>
            <td style="width: 26%;">
                @if($p['enteteGauche'])<div>{!! nl2br(e($p['enteteGauche'])) !!}</div>@endif
                @if($p['academie'])<div>{{ $p['academie'] }}</div>@endif
                @if($p['capLabel'])<div>{{ $p['capLabel'] }}</div>@endif
                <div><b>{{ $p['nomEcole'] }}</b></div>
                @if($p['sousTitre'])<div>{{ $p['sousTitre'] }}</div>@endif
            </td>
            <td class="centre" style="width: 48%;">
                @if($p['logo'])<img class="logo" src="{{ $p['logo'] }}" alt=""><br>@endif
                <h1>Tableau des notes</h1>
                <div class="sous">{{ $classe->nom_classe }} — {{ $periode }} — {{ $p['anneeScolaire'] }}</div>
            </td>
            <td style="width: 26%; text-align: right;">
                @if($p['enteteDroite'])<div>{!! nl2br(e($p['enteteDroite'])) !!}</div>@endif
            </td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th rowspan="{{ $surDix ? 1 : 2 }}" style="width: 9%;">Matricule</th>
                <th rowspan="{{ $surDix ? 1 : 2 }}" style="width: 16%;">Nom et prénom</th>
                @foreach($matieres as $m)
                    <th class="matiere" style="width: {{ $largeurMatiere }}%;"><div>{{ \Illuminate\Support\Str::limit($m->nom_fr ?? $m->nom_matiere, 22, '.') }}</div></th>
                @endforeach
                <th rowspan="{{ $surDix ? 1 : 2 }}" style="width: 6%;">Moyenne</th>
                <th rowspan="{{ $surDix ? 1 : 2 }}" style="width: 5.5%;">Rang</th>
            </tr>
            @unless($surDix)
                <tr>
                    @foreach($matieres as $m)
                        <th class="coef">coef. {{ rtrim(rtrim(number_format((float) $m->coef, 2, ',', ''), '0'), ',') }}</th>
                    @endforeach
                </tr>
            @endunless
        </thead>
        <tbody>
            @foreach($resultats as $r)
                @php $notes = collect($r['matieres'])->keyBy('id_matiere'); @endphp
                <tr>
                    <td class="mat">{{ $r['apercu']->matricule }}</td>
                    <td class="nom">{{ \App\Support\ArabePdf::texte(trim($r['apercu']->nom_eleve.' '.$r['apercu']->prenom_eleve)) }}</td>
                    @foreach($matieres as $m)
                        @php $n = $notes->get($m->id_matiere); @endphp
                        @if($n && ($n->a_des_notes ?? true))
                            <td class="{{ $n->M_Gle < $seuil ? 'faible' : '' }}">{{ $fmt($n->M_Gle) }}</td>
                        @else
                            <td>—</td>
                        @endif
                    @endforeach
                    <td class="moy {{ $r['moyenne_periode'] < $seuil ? 'faible' : '' }}">{{ $fmt($r['moyenne_periode']) }}</td>
                    <td class="rang">{{ $rangTexte($r) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top: 3pt; font-size: 8pt; color: #555;">En rouge : note sous la moyenne ({{ $seuil }}). « — » : pas de note dans la matière pour cette période. Les indicateurs de la classe sont sur la page suivante.</div>

    @php
        $pct = fn (int $n, int $total) => $total ? number_format(100 * $n / $total, 1, ',', '').' %' : '—';
        $fille = fn ($r) => \App\Support\PropositionPassageDonnees::fille($r['apercu']->genre_eleve ?? null);
        // Une ligne d'indicateurs : effectif, ayant / n'ayant pas la moyenne (nombre et %), moyenne, plus forte, plus faible.
        $ligne = function ($valeurs) use ($seuil, $pct, $fmt) {
            $valeurs = collect($valeurs)->values();
            $ont = $valeurs->filter(fn ($x) => $x >= $seuil)->count();
            $n = $valeurs->count();

            return [$n, $ont, $pct($ont, $n), $n - $ont, $pct($n - $ont, $n),
                $n ? $fmt($valeurs->avg()) : '—', $n ? $fmt($valeurs->max()) : '—', $n ? $fmt($valeurs->min()) : '—'];
        };
        $parSexe = [
            'Garçons' => $resultats->reject($fille)->pluck('moyenne_periode'),
            'Filles' => $resultats->filter($fille)->pluck('moyenne_periode'),
            'Ensemble de la classe' => $resultats->pluck('moyenne_periode'),
        ];
        $parMatiere = $matieres->mapWithKeys(fn ($m) => [($m->nom_fr ?? $m->nom_matiere) => $resultats->map(fn ($r) => collect($r['matieres'])->firstWhere('id_matiere', $m->id_matiere))
            ->filter(fn ($n) => $n && ($n->a_des_notes ?? true))->pluck('M_Gle')]);
    @endphp

    {{-- Page à part : tous les indicateurs de la classe, faciles à lire. --}}
    <div class="page-indicateurs">
        <table class="entete">
            <tr>
                <td style="width: 26%;">
                    @if($p['academie'])<div>{{ $p['academie'] }}</div>@endif
                    @if($p['capLabel'])<div>{{ $p['capLabel'] }}</div>@endif
                    <div><b>{{ $p['nomEcole'] }}</b></div>
                </td>
                <td class="centre" style="width: 48%;">
                    <h2>Indicateurs de la classe</h2>
                    <div class="sous-titre">{{ $classe->nom_classe }} — {{ $periode }} — {{ $p['anneeScolaire'] }}</div>
                </td>
                <td style="width: 26%; text-align: right; font-size: 7.5pt; color: #444;">
                    % en <b style="color: #11743a;">vert</b> : au moins la moitié a la moyenne ;<br>en <b style="color: #b00020;">rouge</b> : moins de la moitié.<br>
                    Calculés sur les élèves classés (par matière : ceux qui ont une note).
                </td>
            </tr>
        </table>

        <table class="indicateurs">
            <tr>
                <th rowspan="2" style="width: 22%;"></th><th rowspan="2">Élèves</th>
                <th colspan="2">Ayant la moyenne (≥ {{ $seuil }})</th><th colspan="2">N'ayant pas la moyenne (&lt; {{ $seuil }})</th>
                <th rowspan="2">Moyenne</th><th rowspan="2">Plus forte</th><th rowspan="2">Plus faible</th>
            </tr>
            <tr><th>Nombre</th><th>%</th><th>Nombre</th><th>%</th></tr>
            <tr class="section"><td colspan="9">Moyenne générale, par sexe</td></tr>
            @foreach($parSexe as $libelle => $valeurs)
                @php $c = $ligne($valeurs); $taux = $c[0] ? $c[1] / $c[0] : null; @endphp
                <tr class="{{ str_starts_with($libelle, 'Ensemble') ? 'ensemble' : ($loop->odd ? 'impair' : '') }}">
                    <td class="l">{{ $libelle }}</td>
                    <td>{{ $c[0] }}</td><td>{{ $c[1] }}</td>
                    <td class="{{ $taux === null ? '' : ($taux >= 0.5 ? 'ok' : 'ko') }}">{{ $c[2] }}</td>
                    <td>{{ $c[3] }}</td><td>{{ $c[4] }}</td><td><b>{{ $c[5] }}</b></td><td>{{ $c[6] }}</td><td>{{ $c[7] }}</td>
                </tr>
            @endforeach
            <tr class="section"><td colspan="9">Par matière (élèves qui ont une note)</td></tr>
            @foreach($parMatiere as $libelle => $valeurs)
                <tr class="{{ $loop->odd ? 'impair' : '' }}">
                    <td class="l">{{ $libelle }}</td>
                    @if($valeurs->isEmpty())
                        <td colspan="8" class="vide">Pas de note pour cette période</td>
                    @else
                        @php $c = $ligne($valeurs); $taux = $c[0] ? $c[1] / $c[0] : null; @endphp
                        <td>{{ $c[0] }}</td><td>{{ $c[1] }}</td>
                        <td class="{{ $taux === null ? '' : ($taux >= 0.5 ? 'ok' : 'ko') }}">{{ $c[2] }}</td>
                        <td>{{ $c[3] }}</td><td>{{ $c[4] }}</td><td><b>{{ $c[5] }}</b></td><td>{{ $c[6] }}</td><td>{{ $c[7] }}</td>
                    @endif
                </tr>
            @endforeach
        </table>

        <div style="text-align: right; margin-top: 4pt; font-size: 9pt;">Fait le {{ now()->format('d/m/Y') }} — {{ $p['signataire'] ?? 'Le Directeur' }}</div>
    </div>
</body>
</html>
