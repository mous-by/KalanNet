<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Proposition de passage</title>
    <style>
        @page { margin: 18px 24px 22px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 7.6px; line-height: 1.2; }
        table { border-collapse: collapse; }
        .liste { width: 100%; table-layout: fixed; }
        .liste thead { display: table-header-group; }
        .entete td { border: 0; padding: 0 0 1px; font-size: 8.5px; vertical-align: top; }
        .liste .h { border: 1.1px solid #000; background: #d9d9d9; font-weight: bold; text-align: center; padding: 2px 1px; font-size: 7.2px; }
        .liste td.c { border-left: 1px solid #000; border-right: 1px solid #000; border-bottom: 1px dashed #777; padding: 2px; word-wrap: break-word; }
        .ctr { text-align: center; }
        .sep td.c { border-top: 1.4px dashed #000; }
        .titre { text-align: center; font-size: 11px; font-weight: bold; padding: 3px 0 4px !important; }
        .bas { width: 100%; margin-top: 8px; page-break-inside: avoid; font-size: 8.5px; }
        .bas > tbody > tr > td { vertical-align: top; }
        .recap { width: 100%; border: 1.2px solid #000; }
        .recap td, .recap th { padding: 2px 5px; border-bottom: 1px dashed #777; text-align: center; }
        .recap td:first-child { text-align: left; font-weight: bold; }
        .signature { font-weight: bold; }
    </style>
</head>
<body>
    @php
        $C = \App\Support\PropositionPassageDonnees::class;
        $libelles = ['passe' => 'Passent :', 'redouble' => 'Redoublent :', 'exclu' => 'Exclu(e)s :', 'abandon' => 'Abandons :', 'total' => 'Total :'];
        $precedente = null;
        $n = 0;
        $tableauRecap = function (?array $r) use ($libelles) {
            $h = '<table class="recap"><tr><th></th><th>Garçons</th><th>Filles</th><th>Total</th></tr>';
            foreach ($libelles as $k => $l) {
                $h .= '<tr><td>'.$l.'</td>'.collect(['g', 'f', 't'])->map(fn ($c) => '<td>'.($r ? $r[$k][$c] : '').'</td>')->implode('').'</tr>';
            }

            return $h.'</table>';
        };
    @endphp
    <table class="liste">
        <thead>
            <tr><td style="width: 2.5%; height: 0; padding: 0;"></td><td style="width: 10%; height: 0; padding: 0;"></td><td style="width: 8%; height: 0; padding: 0;"></td><td style="width: 3%; height: 0; padding: 0;"></td><td style="width: 6.5%; height: 0; padding: 0;"></td><td style="width: 7%; height: 0; padding: 0;"></td><td style="width: 8.5%; height: 0; padding: 0;"></td><td style="width: 11%; height: 0; padding: 0;"></td><td style="width: 4%; height: 0; padding: 0;"></td><td style="width: 4%; height: 0; padding: 0;"></td><td style="width: 6%; height: 0; padding: 0;"></td><td style="width: 5%; height: 0; padding: 0;"></td><td style="width: 7%; height: 0; padding: 0;"></td><td style="width: 6%; height: 0; padding: 0;"></td><td style="width: 10.5%; height: 0; padding: 0;"></td></tr>
            <tr><td colspan="15" style="border: 0; padding: 0;">
                <table style="width: 100%;" class="entete">
                    <tr>
                        <td style="width: 60%;">{{ $e['gauche'] }}<br>Académie d'Enseignement : {{ $e['academie'] }}<br>Centre d'Animation Pédagogique : {{ $e['cap'] }}<br><b>{{ $e['ecole'] }}</b></td>
                        <td style="width: 40%; text-align: right;">{!! nl2br(e($e['droite'])) !!}<br>Année Scolaire {{ $e['annee'] }}<br><b>Classe de : {{ $e['classe'] }}</b></td>
                    </tr>
                </table>
                <div class="titre">PROPOSITION DE PASSAGE-REDOUBLEMENT-EXCLUSION ({{ $e['cycle'] }})</div>
            </td></tr>
            <tr>
                <td class="h">N°</td><td class="h">Prénom de l'Élève</td><td class="h">Nom de l'Élève</td>
                <td class="h">Sexe</td><td class="h">Date de Naissance</td><td class="h">Lieu de Naissance</td>
                <td class="h">Prénom du Père</td><td class="h">Prénom et Nom de la Mère</td><td class="h">N° Mle</td>
                <td class="h">Scola-rité</td><td class="h">Class. doublées</td><td class="h">Moyenne Annuelle</td>
                <td class="h">Proposition Conseil Maîtres</td><td class="h">Décision CAP</td><td class="h">Observation</td>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $l)
                @php $n++; $sep = $precedente !== null && $precedente !== ($l['proposition'] ?? null); $precedente = $l['proposition'] ?? null; @endphp
                <tr class="{{ $sep ? 'sep' : '' }}">
                    <td class="c ctr">{{ $n }}</td><td class="c">{{ $l['prenom'] }}</td><td class="c">{{ $l['nom'] }}</td><td class="c ctr">{{ $l['sexe'] }}</td>
                    <td class="c ctr">{{ $l['date_naissance'] }}</td><td class="c">{{ $l['lieu_naissance'] }}</td><td class="c">{{ $l['pere'] }}</td><td class="c">{{ $l['mere'] }}</td>
                    <td class="c ctr">{{ $l['matricule'] }}</td><td class="c ctr">{{ $l['scolarite'] }}</td><td class="c ctr">{{ $l['doublees'] }}</td>
                    <td class="c ctr">{{ isset($l['moyenne']) ? number_format((float) $l['moyenne'], 2, '.', '') : '' }}</td>
                    <td class="c ctr">{{ $C::DECISIONS[$l['proposition'] ?? ''] ?? '' }}</td>
                    <td class="c ctr">{{ $C::DECISIONS[$l['decision_cap'] ?? ''] ?? '' }}</td>
                    <td class="c">{{ $l['observation_cap'] ?? $l['observation'] ?? '' }}</td>
                </tr>
            @endforeach
            <tr><td colspan="15" style="border-top: 1.2px solid #000; height: 1px; padding: 0;"></td></tr>
        </tbody>
    </table>

    <table class="bas">
        <tr>
            <td style="width: 27%; padding-right: 10px;">
                <div style="text-align: center; font-weight: bold; margin-bottom: 2px;">Proposition du Conseil des Maîtres</div>
                {!! $tableauRecap($recap) !!}
            </td>
            <td style="width: 27%; padding-right: 10px;">
                <div style="text-align: center; font-weight: bold; margin-bottom: 2px;">Proposition du CAP</div>
                {!! $tableauRecap($recapCap) !!}
            </td>
            <td style="width: 23%;" class="signature">
                Vu, lu et approuvé<br>{{ $e['localite_cap'] }}, le {{ $fiche?->valide_at?->format('d/m/Y') ?? '……/……/'.now()->year }}<br><br>P/Le DCAP P.O<br>Le Conseiller Pédagogique
            </td>
            <td style="width: 23%; text-align: right;" class="signature">
                <i>{{ $e['lieu'] }}, le {{ $date->format('d/m/Y') }}</i><br><br>Le Directeur d'École,<br><br><br>{{ $directeur }}
            </td>
        </tr>
    </table>
</body>
</html>
