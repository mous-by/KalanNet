<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cahier de bord</title>
    <style>
        @page { margin: 18px 22px; }
        body { font-family: DejaVu Sans, sans-serif; color: #000; font-size: 10px; line-height: 1.25; }
        table { border-collapse: collapse; }
        .entete { width: 100%; font-size: 10px; }
        .entete td { vertical-align: top; padding: 0 0 1px; }
        .titre { text-align: center; font-weight: bold; font-size: 12px; margin: 6px 0 2px; }
        .section { text-align: center; font-weight: bold; font-size: 11.5px; margin: 7px 0 3px; }
        .grille { width: 100%; table-layout: fixed; }
        .grille th, .grille td { border: 1px solid #000; padding: 2px 3px; text-align: center; }
        .grille th { font-size: 9px; }
        .personnes td { font-size: 9px; text-align: left; word-wrap: break-word; }
        .femme { color: #c00000; font-weight: bold; }
        .nb { font-size: 9px; margin-top: 4px; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    @php
        $C = \App\Support\CahierBordDonnees::class;
        $cycle = $d['cycle'] ?? 'premier';
        $jardin = $cycle === 'jardin';
        $n = fn ($v) => $v === null || $v === '' || (int) $v === 0 ? '' : (int) $v;
        $niveaux = $C::niveaux($cycle);
        $effectifs = collect($d['effectifs'] ?? [])->keyBy('cle');
        $total = fn ($champ) => $effectifs->sum(fn ($l) => (int) ($l[$champ] ?? 0));
        $regime = \App\Support\RapportSuccinctDonnees::RECRUTEMENTS[$d['regime'] ?? 0] ?? '';
        $entete = function () use ($d, $jardin, $regime, $annee, $cycle) {
            return view()->make('pdf.partials.cahier_bord_entete', compact('d', 'jardin', 'regime', 'annee', 'cycle'))->render();
        };
    @endphp

    <div class="page">
        {!! $entete() !!}

        <div class="section">I. SITUATION DES EFFECTIFS</div>
        <table class="grille">
            <tr>
                <th rowspan="2" style="width: 4%;"></th>
                @foreach($niveaux as $cle => $libelle)
                    <th colspan="3">{{ $libelle }} (Nbre : {{ $n($effectifs[$cle]['groupes'] ?? 0) }})</th>
                @endforeach
                <th colspan="3">{{ $jardin ? 'Total général sections' : 'Total général' }} (Nbre : {{ $n($total('groupes')) }})</th>
            </tr>
            <tr>
                @foreach($niveaux as $cle => $libelle)
                    <th>{{ $jardin ? 'Nveau' : ((string) $cle === '1' ? 'Recr' : 'Pass.') }}</th><th>{{ $jardin ? 'Ancien' : 'Red' }}</th><th>T</th>
                @endforeach
                <th>{{ $jardin ? 'Nveau' : 'Pass.' }}</th><th>{{ $jardin ? 'Ancien' : 'Red' }}</th><th>T</th>
            </tr>
            @foreach(['G' => ['g', 'rg'], 'F' => ['f', 'rf'], 'T' => [null, null]] as $sexe => [$eleves, $red])
                <tr>
                    <th>{{ $sexe }}</th>
                    @foreach([...array_keys($niveaux), 'total'] as $cle)
                        @php
                            $ligne = $cle === 'total' ? ['g' => $total('g'), 'f' => $total('f'), 'rg' => $total('rg'), 'rf' => $total('rf')] : ($effectifs[$cle] ?? []);
                            $tous = $eleves ? (int) ($ligne[$eleves] ?? 0) : (int) ($ligne['g'] ?? 0) + (int) ($ligne['f'] ?? 0);
                            $redoublants = $red ? (int) ($ligne[$red] ?? 0) : (int) ($ligne['rg'] ?? 0) + (int) ($ligne['rf'] ?? 0);
                        @endphp
                        <td>{{ $n($tous - $redoublants) }}</td><td>{{ $n($redoublants) }}</td><td><b>{{ $n($tous) }}</b></td>
                    @endforeach
                </tr>
            @endforeach
        </table>

        <div class="section">II. LISTE DU PERSONNEL ADMINISTRATIF (Directeur, Chargé de dossiers, Gardien)</div>
        @include('pdf.partials.cahier_bord_personnes', ['personnes' => $d['administratifs'] ?? [], 'enseignants' => false, 'jardin' => $jardin])

        <table style="width: 100%; margin-top: 4px;">
            <tr>
                <td style="width: 52%; vertical-align: top;">
                    <div class="section" style="text-align: left;">III. SALLES DE CLASSE CONSTRUITES EN</div>
                    <table class="grille">
                        <tr><th></th>@foreach($C::SALLES as $libelle)<th>{{ $libelle }}</th>@endforeach<th>TOTAL</th></tr>
                        <tr><th>Nbre</th>@foreach($C::SALLES as $cle => $libelle)<td>{{ $n($d['salles'][$cle] ?? null) }}</td>@endforeach<td><b>{{ $n(collect($d['salles'] ?? [])->sum(fn ($v) => (int) $v)) }}</b></td></tr>
                    </table>
                </td>
                <td style="width: 48%; vertical-align: bottom; padding-left: 20px; font-weight: bold;">
                    Date de création {{ $jardin ? 'du CDPE' : "de l'école" }} : {{ $d['date_creation'] ?? '' }}<br>
                    Date d'ouverture {{ $jardin ? 'du CDPE' : "de l'école" }} : {{ $d['date_ouverture'] ?? '' }}
                </td>
            </tr>
        </table>

        <div class="section">IV. SITUATION DES TABLES-BANCS</div>
        <table style="width: 100%;">
            <tr>
                <td style="width: 66%; vertical-align: top;">
                    <table class="grille">
                        <tr><th colspan="2" style="width: 30%;">Tables-bancs et chaises</th>@foreach($niveaux as $libelle)<th>{{ $jardin ? str_replace(['Section des ', 'Petits', 'Moyens', 'Grands'], ['', 'S.P', 'S.M', 'S.G'], $libelle) : $libelle }}</th>@endforeach<th>TOTAL</th></tr>
                        @foreach($C::TABLES as $type => $libelle)
                            <tr>
                                <td colspan="2" style="text-align: left;">{{ $libelle }}</td>
                                @foreach($niveaux as $cle => $x)<td>{{ $n($d['tables'][$cle][$type] ?? null) }}</td>@endforeach
                                <td><b>{{ $n(collect($d['tables'] ?? [])->sum(fn ($l) => (int) ($l[$type] ?? 0))) }}</b></td>
                            </tr>
                        @endforeach
                    </table>
                </td>
                <td style="width: 34%; vertical-align: top; padding-left: 16px;">
                    <table class="grille">
                        @foreach($C::EQUIPEMENTS as $cle => $libelle)
                            <tr><td style="text-align: left;">Nbre {{ Str::lower($libelle) }}</td><td style="width: 22%;">{{ $n($d['equipements'][$cle] ?? null) }}</td></tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>
        <div class="nb">N.B : {{ $jardin ? 'Anciens = redoublants ; Nouveaux = recrutés ou passants.' : 'Recr = recruté ; Red = redoublant ; Nbre = nombre de cours.' }}</div>
    </div>

    <div class="page">
        <div class="section" style="margin-top: 0;">V. LISTE DU PERSONNEL ENSEIGNANT {{ $jardin ? "DU JARDIN D'ENFANTS" : '('.Str::upper($C::CYCLES[$cycle]).')' }}</div>
        @include('pdf.partials.cahier_bord_personnes', ['personnes' => $d['enseignants'] ?? [], 'enseignants' => true, 'jardin' => $jardin])
        <table style="width: 100%; margin-top: 8px;">
            <tr>
                <td class="nb" style="vertical-align: top;">
                    N.B : Préciser MSC ou MPC. Grade actuel : MSC 2-4 ou MPC 2-6.<br>
                    S.F : Situation de famille (marié(e), célibataire, divorcé(e) ou veuf(ve)).<br>
                    <span class="femme">Nom des femmes écrit en rouge.</span>
                </td>
                <td style="text-align: right; vertical-align: top; font-weight: bold;">
                    Fiche établie le {{ now()->format('d/m/Y') }}<br><br>Signature et cachet du Directeur<br><br><br>{{ $d['directeur'] ?? '' }}
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
