<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport succinct de rentrée</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 9px; }
        table { border-collapse: collapse; }
        .entete { width: 100%; font-size: 9.5px; }
        .entete td { vertical-align: top; }
        h1 { text-align: center; font-size: 13px; margin: 10px 0 8px; text-transform: uppercase; }
        .titre { font-weight: bold; font-size: 10px; margin: 12px 0 4px; }
        .grille { width: 100%; }
        .grille th, .grille td { border: 1px solid #000; padding: 3px 2px; text-align: center; }
        .grille th { background: #ececec; font-size: 8px; }
        .grille td.l { text-align: left; padding-left: 4px; }
        .grille .total td { font-weight: bold; background: #f3f3f3; }
        .pied { width: 100%; margin-top: 26px; font-size: 10px; }
    </style>
</head>
<body>
    @php
        // Modèle de l'académie : « Rapport succinct de rentrée ESG » (cinq tableaux).
        $n = fn ($v) => (int) $v === 0 ? '0' : (int) $v;
        $lignes = collect($d['groupes'] ?? []);
        $niveaux = ['n1' => '10ÈME', 'n2' => '11ÈME', 'n3' => '12ÈME'];
        $parNiveau = $lignes->groupBy(fn ($l) => $l['niveau'] ?? 'n1');
        $colonnes = collect($niveaux)->keys()->flatMap(fn ($niv) => $parNiveau->get($niv, collect()))->values();
        $sigle = $d['ecole'] ?? '';
        $public = (int) ($d['statut'] ?? 0) === 1;
        $ens = $d['enseignants'] ?? ['ph' => 0, 'pf' => 0, 'vh' => 0, 'vf' => 0];
        $somme = fn ($champ) => $lignes->sum($champ);
        $academie = trim(preg_replace("/^\s*(ae|acad[ée]mie( d'enseignement)?)\s+(de |d')?/iu", '', (string) ($d['academie'] ?? '')));
    @endphp

    <table class="entete">
        <tr>
            <td>MINISTÈRE DE L'ÉDUCATION NATIONALE<br>**********<br>ACADÉMIE D'ENSEIGNEMENT DE {{ Str::upper($academie) }}<br>*************<br>LYCÉE : {{ $sigle }}<br>TÉL : {{ $d['telephone'] ?? '' }}</td>
            <td style="text-align: right;">RÉPUBLIQUE DU MALI<br>Un Peuple - Un But - Une Foi</td>
        </tr>
    </table>
    <h1>Rapport succinct de rentrée {{ $annee }}</h1>

    <div class="titre">Tableau n°1 : Répartition des enseignants par genre et par statut</div>
    <table class="grille">
        <tr><th rowspan="2" style="width: 22%;">ÉTABLISSEMENT</th><th colspan="3">Permanents</th><th colspan="3">Vacataires</th><th colspan="3">Total</th></tr>
        <tr><th>H</th><th>F</th><th>T</th><th>H</th><th>F</th><th>T</th><th>H</th><th>F</th><th>T</th></tr>
        <tr>
            <td class="l">{{ $sigle }}</td>
            <td>{{ $n($ens['ph']) }}</td><td>{{ $n($ens['pf']) }}</td><td><b>{{ $ens['ph'] + $ens['pf'] }}</b></td>
            <td>{{ $n($ens['vh']) }}</td><td>{{ $n($ens['vf']) }}</td><td><b>{{ $ens['vh'] + $ens['vf'] }}</b></td>
            <td>{{ $ens['ph'] + $ens['vh'] }}</td><td>{{ $ens['pf'] + $ens['vf'] }}</td><td><b>{{ $ens['ph'] + $ens['pf'] + $ens['vh'] + $ens['vf'] }}</b></td>
        </tr>
    </table>

    <div class="titre">Tableau n°2 : Répartition des élèves par année d'étude et par série</div>
    <table class="grille">
        <tr>
            <th rowspan="3" style="width: 12%;">ÉTABLISSEMENT</th><th rowspan="3" style="width: 8%;">Élèves</th>
            @foreach($niveaux as $niv => $libelle)
                @if($parNiveau->has($niv))<th colspan="{{ 3 * $parNiveau[$niv]->count() }}">{{ $libelle }}</th>@endif
            @endforeach
            <th colspan="3" rowspan="2">TOTAL</th>
        </tr>
        <tr>@foreach($colonnes as $l)<th colspan="3">{{ $l['libelle'] }}</th>@endforeach</tr>
        <tr>@foreach($colonnes as $l)<th>G</th><th>F</th><th>T</th>@endforeach<th>G</th><th>F</th><th>T</th></tr>
        @foreach(['Public' => $public, 'Privé' => !$public] as $statut => $actif)
            <tr>
                @if($loop->first)<td rowspan="3" class="l">{{ $sigle }}</td>@endif
                <td class="l">{{ $statut }}</td>
                @foreach($colonnes as $l)
                    <td>{{ $actif ? $n($l['g']) : '0' }}</td><td>{{ $actif ? $n($l['f']) : '0' }}</td><td><b>{{ $actif ? $l['g'] + $l['f'] : 0 }}</b></td>
                @endforeach
                <td>{{ $actif ? $somme('g') : 0 }}</td><td>{{ $actif ? $somme('f') : 0 }}</td><td><b>{{ $actif ? $somme('g') + $somme('f') : 0 }}</b></td>
            </tr>
        @endforeach
        <tr class="total">
            <td class="l">Eff. total</td>
            @foreach($colonnes as $l)<td>{{ $l['g'] }}</td><td>{{ $l['f'] }}</td><td>{{ $l['g'] + $l['f'] }}</td>@endforeach
            <td>{{ $somme('g') }}</td><td>{{ $somme('f') }}</td><td>{{ $somme('g') + $somme('f') }}</td>
        </tr>
    </table>

    <div class="titre">Tableau n°3 : Répartition des effectifs par série / année d'étude (passants et redoublants)</div>
    <table class="grille">
        <tr>
            <th rowspan="3" style="width: 12%;">ÉTABLISSEMENT</th><th rowspan="3" style="width: 8%;">Élèves</th>
            @foreach($niveaux as $niv => $libelle)
                @if($parNiveau->has($niv))<th colspan="{{ 3 * $parNiveau[$niv]->count() }}">{{ $libelle }}</th>@endif
            @endforeach
            <th colspan="3" rowspan="2">TOTAL</th>
        </tr>
        <tr>@foreach($colonnes as $l)<th colspan="3">{{ $l['libelle'] }}</th>@endforeach</tr>
        <tr>@foreach($colonnes as $l)<th>G</th><th>F</th><th>T</th>@endforeach<th>G</th><th>F</th><th>T</th></tr>
        @foreach(['Eff. total' => fn ($l, $s) => $l[$s], 'Passants' => fn ($l, $s) => $l[$s] - $l['r'.$s], 'Redoublants' => fn ($l, $s) => $l['r'.$s]] as $libelle => $valeur)
            <tr>
                @if($loop->first)<td rowspan="3" class="l">{{ $sigle }}</td>@endif
                <td class="l">{{ $libelle }}</td>
                @foreach($colonnes as $l)
                    <td>{{ $valeur($l, 'g') }}</td><td>{{ $valeur($l, 'f') }}</td><td><b>{{ $valeur($l, 'g') + $valeur($l, 'f') }}</b></td>
                @endforeach
                @php $tg = $lignes->sum(fn ($l) => $valeur($l, 'g')); $tf = $lignes->sum(fn ($l) => $valeur($l, 'f')); @endphp
                <td>{{ $tg }}</td><td>{{ $tf }}</td><td><b>{{ $tg + $tf }}</b></td>
            </tr>
        @endforeach
    </table>

    <div class="titre">Tableau n°4 : Répartition des groupes pédagogiques par niveau</div>
    <table class="grille" style="width: 60%;">
        <tr><th style="width: 25%;"></th>@foreach($niveaux as $libelle)<th>{{ ucfirst(Str::lower($libelle)) }}</th>@endforeach<th>Total</th></tr>
        <tr>
            <td class="l">GP</td>
            @foreach($niveaux as $niv => $libelle)<td>{{ $parNiveau->get($niv, collect())->sum('groupes') }}</td>@endforeach
            <td><b>{{ $somme('groupes') }}</b></td>
        </tr>
    </table>

    <div class="titre">Tableau n°5 : Répartition des salles de classe par niveau</div>
    <table class="grille" style="width: 60%;">
        <tr><th style="width: 25%;"></th>@foreach($niveaux as $libelle)<th>{{ ucfirst(Str::lower($libelle)) }}</th>@endforeach<th>Total</th></tr>
        <tr>
            <td class="l">SDC</td>
            @foreach($niveaux as $niv => $libelle)<td>{{ $d['salles_niveaux'][$niv] ?? '' }}</td>@endforeach
            <td><b>{{ collect($d['salles_niveaux'] ?? [])->sum() ?: '' }}</b></td>
        </tr>
    </table>

    <table class="pied">
        <tr>
            <td style="width: 55%;"></td>
            <td style="text-align: center;">Fait à …………………………, le ……/……/20……<br><br><b>LE PROVISEUR</b></td>
        </tr>
    </table>
</body>
</html>
