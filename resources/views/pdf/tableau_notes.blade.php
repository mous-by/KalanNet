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
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;">Moyenne de la matière</td>
                @foreach($matieres as $m)
                    @php
                        $valeurs = $resultats->map(fn ($r) => collect($r['matieres'])->firstWhere('id_matiere', $m->id_matiere))
                            ->filter(fn ($n) => $n && ($n->a_des_notes ?? true))->pluck('M_Gle');
                    @endphp
                    <td>{{ $valeurs->isNotEmpty() ? $fmt($valeurs->avg()) : '—' }}</td>
                @endforeach
                <td>{{ $fmt($stats['classe']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <table class="bas">
        <tr>
            <td class="stat" style="width: 60%;">
                Élèves classés : <b>{{ $stats['effectif'] }}</b> &nbsp;·&nbsp;
                Moyenne de la classe : <b>{{ $fmt($stats['classe']) }}</b> &nbsp;·&nbsp;
                Plus forte : <b>{{ $fmt($stats['premier']) }}</b> &nbsp;·&nbsp;
                Plus faible : <b>{{ $fmt($stats['dernier']) }}</b> &nbsp;·&nbsp;
                Au-dessus de la moyenne ({{ $seuil }}) : <b>{{ $resultats->filter(fn ($r) => $r['moyenne_periode'] >= $seuil)->count() }}</b>
                <div style="margin-top: 3px; color: #555;">En rouge : note sous la moyenne. « — » : pas de note dans la matière pour cette période.</div>
            </td>
            <td style="width: 40%; text-align: right;">
                Fait le {{ now()->format('d/m/Y') }}<br>
                {{ $p['signataire'] ?? 'Le Directeur' }}
            </td>
        </tr>
    </table>
</body>
</html>
