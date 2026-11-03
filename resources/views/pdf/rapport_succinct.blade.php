<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport succinct de rentrée</title>
    <style>
        @page { margin: 26px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10.5px; line-height: 1.3; }
        table { border-collapse: collapse; }
        .annee { text-align: right; font-size: 12px; font-weight: bold; }
        h1 { text-align: center; font-size: 15px; margin: 2px 0 0; letter-spacing: .5px; text-transform: uppercase; }
        .sous { text-align: center; font-size: 10px; margin-bottom: 10px; }
        .t { text-align: center; font-size: 10.5px; font-weight: bold; margin: 10px 0 4px; }
        .cadre { width: 100%; table-layout: fixed; }
        .cadre > tbody > tr > td { vertical-align: top; }
        .info { width: 100%; table-layout: fixed; border: 1px solid #000; }
        .info td { padding: 5px 6px; vertical-align: bottom; }
        .info .l { width: 82px; }
        .info .v { font-weight: bold; border-bottom: 1px dotted #555; word-wrap: break-word; }
        .choix { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .choix td { border: 1px solid #000; padding: 3px 5px; vertical-align: middle; font-size: 9.5px; }
                .opt { display: inline-block; margin-right: 6px; white-space: nowrap; line-height: 16px; }
        .opt b { font-weight: normal; padding: 0 3px; }
        .opt.on b { border: 1.2px solid #000; border-radius: 9px; padding: 0 4px; font-weight: bold; }
        .opt.on { font-weight: bold; }
        .grille { width: 100%; table-layout: fixed; }
        .grille th, .grille td { border: 1px solid #000; padding: 1px 5px; text-align: center; }
        .grille th { font-weight: normal; padding: 2px 4px; }
        .sexe { text-align: left !important; }
        .total td { font-weight: bold; background: #e5e5e5; }
        .pied { margin-top: 12px; font-size: 9.5px; }
    </style>
</head>
<body>
    @php
        $choix = fn (array $options, $valeur) => collect($options)->map(fn ($libelle, $n) => '<span class="opt '.((int) $valeur === $n ? 'on' : '').'"><b>'.$n.'</b> '.e($libelle).'</span>')->implode(' ');
        $fmt = fn ($n) => (int) $n === 0 ? '' : (int) $n;
        $C = \App\Support\RapportSuccinctDonnees::class;
        $secondaire = ($d['portee'] ?? 'fondamental') === 'secondaire';
        $cycles = $secondaire ? ['general' => 'Secondaire général', 'technique' => 'Technique et professionnel'] : ['prescolaire' => 'Préscolaire', 'premier' => 'Premier cycle', 'second' => 'Second cycle'];
    @endphp

    <div class="annee">{{ $annee }}</div>
    <h1>Rapport succinct de rentrée</h1>
    <div class="sous">(Formulaire à remplir par les directeurs d'établissement)</div>

    <div class="t">Tableau 1 : RENSEIGNEMENTS GÉNÉRAUX</div>
    <table class="cadre">
        <tr>
            <td style="width: 38%; padding-right: 7px;">
                <table class="info">
                    <tr><td class="l">{{ $secondaire ? 'Établissement :' : 'École de :' }}</td><td class="v">{{ $d['ecole'] ?? '' }}</td></tr>
                    <tr><td class="l">Village/Quartier :</td><td class="v">{{ $d['quartier'] ?? '' }}</td></tr>
                    <tr><td class="l">Commune de :</td><td class="v">{{ $d['commune'] ?? '' }}</td></tr>
                    @unless($secondaire)
                        <tr><td class="l">CAP de :</td><td class="v">{{ $d['cap'] ?? '' }}</td></tr>
                    @endunless
                    <tr><td class="l">Cercle de :</td><td class="v">{{ $d['cercle'] ?? '' }}</td></tr>
                    <tr><td class="l">AE de :</td><td class="v">{{ $d['academie'] ?? '' }}</td></tr>
                </table>
            </td>
            <td style="width: 62%;">
                <table class="choix">
                    <colgroup><col style="width: 25%;"><col style="width: 75%;"></colgroup>
                    @if($secondaire)
                        <tr><td class="n">Enseignement</td><td>{!! $choix($C::CYCLES_SECONDAIRE, $d['cycle'] ?? null) !!}</td></tr>
                        <tr><td class="n">Statut administratif</td><td>{!! $choix($C::STATUTS, $d['statut'] ?? null) !!}</td></tr>
                    @else
                        <tr><td class="n">Cycles d'enseignement</td><td>{!! $choix($C::CYCLES, $d['cycle'] ?? null) !!}</td></tr>
                        <tr><td class="n">Statut administratif de l'école</td><td>{!! $choix($C::STATUTS, $d['statut'] ?? null) !!}</td></tr>
                        <tr><td class="n">Enseignement</td><td>{!! $choix($C::ENSEIGNEMENTS, $d['enseignement'] ?? null) !!}</td></tr>
                        <tr><td class="n">Mode de recrutement</td><td>{!! $choix($C::RECRUTEMENTS, $d['recrutement'] ?? null) !!}</td></tr>
                        <tr><td class="n">École à pédagogie convergente : langue</td><td><b>{{ $d['langue'] ?? '' }}</b></td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="t">Tableau 2 : GROUPES PÉDAGOGIQUES ET EFFECTIFS SCOLAIRES</div>
    <table class="grille">
        <tr>
            <th rowspan="2" style="width: 17%;">{{ $secondaire ? 'Classe / série' : "Année d'études" }}</th>
            <th rowspan="2" style="width: 15%;">Nombre de groupes pédagogiques</th>
            <th rowspan="2" style="width: 18%;">Nombre d'élèves par sexe</th>
            <th rowspan="2" style="width: 14%;">Total élèves</th>
            <th colspan="2">Redoublants</th>
        </tr>
        <tr><th style="width: 18%;">Sexe</th><th style="width: 18%;">Total</th></tr>
        @foreach($d['groupes'] ?? [] as $l)
            <tr>
                <td rowspan="2">{{ $l['libelle'] }}</td>
                <td rowspan="2">{{ $fmt($l['groupes']) }}</td>
                <td class="sexe">G : <b>{{ $fmt($l['g']) }}</b></td>
                <td rowspan="2"><b>{{ $fmt($l['g'] + $l['f']) }}</b></td>
                <td class="sexe">G : <b>{{ $fmt($l['rg']) }}</b></td>
                <td rowspan="2"><b>{{ $fmt($l['rg'] + $l['rf']) }}</b></td>
            </tr>
            <tr>
                <td class="sexe">F : <b>{{ $fmt($l['f']) }}</b></td>
                <td class="sexe">F : <b>{{ $fmt($l['rf']) }}</b></td>
            </tr>
        @endforeach
        <tr class="total">
            <td rowspan="2">{{ $secondaire ? 'Total établissement' : 'Total école' }}</td>
            <td rowspan="2">{{ $totaux['groupes'] }}</td>
            <td class="sexe">G : {{ $totaux['g'] }}</td>
            <td rowspan="2">{{ $totaux['eleves'] }}</td>
            <td class="sexe">G : {{ $totaux['rg'] }}</td>
            <td rowspan="2">{{ $totaux['redoublants'] }}</td>
        </tr>
        <tr class="total">
            <td class="sexe">F : {{ $totaux['f'] }}</td>
            <td class="sexe">F : {{ $totaux['rf'] }}</td>
        </tr>
    </table>

    <div class="t">Tableau 3 : {{ $secondaire ? 'ENSEIGNANTS' : 'MAÎTRES EN CLASSE' }} ET SALLES DE CLASSE UTILISÉES</div>
    <table class="grille">
        <tr><th style="width: 30%;">Cycle d'enseignement</th><th>Permanents</th><th>Contractuels</th><th>Total</th><th>Nbre de salles de classe</th></tr>
        @foreach($cycles as $cle => $libelle)
            @php $m = $d['maitres'][$cle] ?? null; @endphp
            @if($m || ($cle !== 'prescolaire' && !$secondaire))
                <tr>
                    <td>{{ $libelle }}</td>
                    <td>{{ $fmt($m['permanents'] ?? 0) }}</td>
                    <td>{{ $fmt($m['contractuels'] ?? 0) }}</td>
                    <td><b>{{ $fmt(($m['permanents'] ?? 0) + ($m['contractuels'] ?? 0)) }}</b></td>
                    <td>{{ $fmt($m['salles'] ?? 0) }}</td>
                </tr>
            @endif
        @endforeach
    </table>

    <div class="pied">Cette fiche doit être remplie et envoyée {{ $secondaire ? "à l'académie" : 'au CAP' }} dans les 15 jours qui suivent la rentrée scolaire.</div>
</body>
</html>
