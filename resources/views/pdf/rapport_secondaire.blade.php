<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ \App\Support\RapportSecondaireDefinitions::TYPES[$type] }}</title>
    <style>
        @page { margin: 18px 22px; }
        body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 10px; line-height: 1.25; }
        table { border-collapse: collapse; }
        .entete { width: 100%; font-size: 10px; }
        .entete td { vertical-align: top; }
        h1 { text-align: center; font-size: 14px; margin: 8px 0 2px; text-transform: uppercase; }
        .sous { text-align: center; font-size: 11px; font-weight: bold; margin-bottom: 8px; }
        .bloc { page-break-inside: avoid; margin-bottom: 9px; }
        .titre { font-weight: bold; font-size: 10.5px; margin: 4px 0 3px; }
        .grille { width: 100%; }
        .grille th, .grille td { border: 1px solid #000; padding: 2px 3px; text-align: center; }
        .grille th { font-size: 8.5px; background: #ececec; }
        .grille td.l { text-align: left; }
        .grille .total td { font-weight: bold; background: #f3f3f3; }
        .infos { width: 100%; }
        .infos td { padding: 2px 4px; border-bottom: 1px dotted #888; width: 25%; }
        .note { font-size: 8.5px; margin-top: 2px; }
        /* Partie administrative : présentation d'un rapport officiel (Times New Roman 12, interligne 1,5). */
        .redaction { font-family: Times, 'Times New Roman', serif; font-size: 12pt; line-height: 1.5; }
        .redaction .rubrique { font-weight: bold; font-size: 12pt; margin: 8pt 0 2pt; }
        .redaction p { text-align: justify; margin: 0 0 6pt; }
    </style>
</head>
<body>
    @php
        $i = $d['infos'] ?? [];
        $n = fn ($v) => $v === null || $v === '' ? '' : $v;
    @endphp
    <table class="entete">
        <tr>
            <td>MINISTÈRE DE L'ÉDUCATION NATIONALE<br>DIRECTION NATIONALE DE L'ENSEIGNEMENT SECONDAIRE<br>ACADÉMIE D'ENSEIGNEMENT DE {{ Str::upper(trim(preg_replace("/^\\s*(ae|acad[ée]mie( d'enseignement)?)\\s+(de |d')?/iu", '', (string) $academie))) }}</td>
            <td style="text-align: right;">RÉPUBLIQUE DU MALI<br>Un Peuple - Un But - Une Foi</td>
        </tr>
    </table>
    <h1>{{ \App\Support\RapportSecondaireDefinitions::TYPES[$type] }}</h1>
    <div class="sous">{{ $i['nom'] ?? '' }} : année scolaire {{ $annee }}</div>

    <div class="redaction">
        <div class="rubrique" style="font-size: 13pt;">PARTIE ADMINISTRATIVE</div>
        @foreach($redaction as $cle => [$titre])
            <div class="rubrique">{{ $titre }}</div>
            {{-- Un paragraphe par bloc de texte : la dernière ligne d'un paragraphe justifié n'est pas étirée. --}}
            @foreach(preg_split("/\R+/u", trim((string) ($d['redaction'][$cle] ?? ''))) as $paragraphe)
                @if(trim($paragraphe) !== '')<p>{{ trim($paragraphe) }}</p>@endif
            @endforeach
        @endforeach
    </div>

    <div class="titre" style="font-size: 12px; margin-top: 10px;">DONNÉES STATISTIQUES</div>
    <div class="bloc">
        <div class="titre">T0 : INFORMATIONS GÉNÉRALES</div>
        <table class="infos">
            @foreach(collect($infos)->chunk(2) as $paire)
                <tr>
                    @foreach($paire as $champ => $libelle)
                        <td>{{ $libelle }} :</td><td><b>{{ $n($i[$champ] ?? null) }}</b></td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>

    @foreach($tableaux as $tableau)
        @php
            $colonnes = collect($tableau['colonnes'])->flatMap(fn ($g) => $g['cols'])->all();
            $groupee = collect($tableau['colonnes'])->contains(fn ($g) => $g['libelle'] !== null);
            $totaux = $tableau['totaux'] ?? null;
            $avecTotalLigne = in_array($totaux, ['colonne', 'les deux'], true);
            $valeurs = $d['t'][$tableau['code']] ?? [];
            // Seulement les colonnes de ce tableau : T7, T8 et T9 sont coupés en plusieurs tableaux qui partagent leurs données.
            $somme = fn (array $ligne) => collect($ligne)->only(array_keys($colonnes))->sum(fn ($v) => is_numeric($v) ? (int) $v : 0);
        @endphp
        <div class="bloc">
            <div class="titre">{{ $tableau['numero'] ?? $tableau['code'] }} : {{ Str::upper($tableau['titre']) }}</div>
            <table class="grille">
                @if($groupee)
                    <tr>
                        <th rowspan="2"></th>
                        @foreach($tableau['colonnes'] as $groupe)<th colspan="{{ count($groupe['cols']) }}">{{ $groupe['libelle'] }}</th>@endforeach
                        @if($avecTotalLigne)<th rowspan="2">Total</th>@endif
                    </tr>
                @endif
                <tr>
                    @unless($groupee)<th></th>@endunless
                    @foreach($colonnes as $libelle)<th>{{ $libelle }}</th>@endforeach
                    @if(!$groupee && $avecTotalLigne)<th>Total</th>@endif
                </tr>
                @php
                    // Listes (salles, personnel…) : seulement les lignes remplies, renumérotées.
                    $lignesAffichees = !empty($tableau['liste'])
                        ? collect($tableau['lignes'])->filter(fn ($l, $cle) => !empty($valeurs[$cle]))->keys()->values()->mapWithKeys(fn ($cle, $i) => [$cle => (string) ($i + 1)])->all()
                        : $tableau['lignes'];
                @endphp
                @if(!empty($tableau['liste']) && $lignesAffichees === [])
                    <tr><td class="l" colspan="{{ count($colonnes) + 1 }}">Néant</td></tr>
                @endif
                @foreach($lignesAffichees as $ligne => $libelle)
                    <tr>
                        <td class="l">{{ $libelle }}</td>
                        @foreach($colonnes as $colonne => $l)<td class="{{ ($tableau['type'] ?? '') === 'texte' ? 'l' : '' }}">{{ $n($valeurs[$ligne][$colonne] ?? null) }}</td>@endforeach
                        @if($avecTotalLigne)<td><b>{{ $somme($valeurs[$ligne] ?? []) }}</b></td>@endif
                    </tr>
                @endforeach
                @if(in_array($totaux, ['ligne', 'les deux'], true))
                    <tr class="total">
                        <td class="l">Total</td>
                        @foreach($colonnes as $colonne => $l)<td>{{ collect($valeurs)->only(array_keys($tableau["lignes"]))->sum(fn ($v) => (int) ($v[$colonne] ?? 0)) }}</td>@endforeach
                        @if($avecTotalLigne)<td>{{ collect($valeurs)->only(array_keys($tableau["lignes"]))->sum(fn ($v) => $somme($v)) }}</td>@endif
                    </tr>
                @endif
            </table>
            @if(!empty($tableau['note']))<div class="note">{{ $tableau['note'] }}</div>@endif
        </div>
    @endforeach

    <table style="width: 100%; margin-top: 12px;">
        <tr><td></td><td style="text-align: right; font-weight: bold;">Fait le {{ now()->format('d/m/Y') }}<br><br>Signature et cachet du Proviseur</td></tr>
    </table>

    @if($annexes)
        <div class="titre" style="font-size: 12px; page-break-before: always;">ANNEXES</div>
        @foreach($annexes as $annexe)
            <div style="margin-bottom: 9px;">
                <div class="titre">{{ $annexe['titre'] }}</div>
                <table class="grille">
                    <tr>@foreach($annexe['entetes'] as $entete)<th>{{ $entete }}</th>@endforeach</tr>
                    @forelse($annexe['lignes'] as $ligne)
                        <tr>@foreach($ligne as $valeur)<td class="l">{{ $valeur }}</td>@endforeach</tr>
                    @empty
                        <tr><td class="l" colspan="{{ count($annexe['entetes']) }}">Aucun élève.</td></tr>
                    @endforelse
                </table>
            </div>
        @endforeach
    @endif
</body>
</html>
