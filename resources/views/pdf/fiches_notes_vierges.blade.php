<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiches de notes</title>
    <style>
        @page { margin: 22px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10.5px; line-height: 1.3; }
        .header { width: 100%; margin-bottom: 6px; border-bottom: 1px solid #000; padding-bottom: 4px; }
        .header td { vertical-align: top; }
        .text-right { text-align: right; }
        .school-name { text-align: center; font-size: 13px; font-weight: 700; text-transform: uppercase; margin: 6px 0 2px; }
        .title { text-align: center; font-size: 17px; font-weight: bold; text-transform: uppercase; margin: 2px 0 6px; }
        .context { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .context td { border: 1px solid #9ca3af; padding: 5px 7px; }
        .context strong { color: #374151; }
        .blank { display: inline-block; border-bottom: 1px dotted #111827; min-width: 110px; }
        .consigne { font-size: 9.5px; color: #374151; margin-bottom: 6px; }
        .list { width: 100%; border-collapse: collapse; }
        .list th { background: #e5e7eb; border: 1px solid #6b7280; padding: 4px; font-weight: 700; text-align: left; }
        .list td { border: 1px solid #6b7280; padding: 0 4px; height: 19px; }
        .num { width: 22px; text-align: center; }
        .matricule { width: 92px; font-size: 9px; }
        .note { width: 58px; text-align: center !important; }
        .obs { width: 90px; }
        .date-label { text-align: right !important; font-weight: normal; font-style: italic; background: #f3f4f6 !important; }
        .date-cell { background: #fff !important; height: 16px; }
        .footer { width: 100%; margin-top: 14px; }
        .footer td { width: 50%; vertical-align: top; padding-right: 12px; }
        .line { margin-top: 30px; border-top: 1px solid #111827; padding-top: 3px; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
@php
    $bareme = fn (float $sur) => rtrim(rtrim(number_format($sur, 2, ',', ''), '0'), ',');
@endphp
@foreach($fiches as $fiche)
    <div class="{{ $loop->last ? '' : 'page-break' }}">
        @include('pdf.partials.entete_ecole')

        @include('pdf.partials.nom_ecole_logo')
        <div class="title">Fiche de notes</div>

        <table class="context">
            <tr>
                <td><strong>Classe :</strong> {{ $classe->nom_classe }}</td>
                <td><strong>Matière :</strong> {{ $fiche['matiere']->nom_matiere }}</td>
                <td><strong>Année scolaire :</strong> {{ $annee->annee }}</td>
            </tr>
            <tr>
                <td><strong>Enseignant(e) :</strong> @if($fiche['enseignant']) {{ $fiche['enseignant'] }} @else <span class="blank"></span> @endif</td>
                <td><strong>Période :</strong> @if($periodeLibelle) {{ $periodeLibelle }} @else <span class="blank"></span> @endif</td>
                <td><strong>Effectif :</strong> {{ $fiche['eleves']->count() }} élève(s)</td>
            </tr>
        </table>

        <div class="consigne">
            Notes écrites lisiblement, sur le barème indiqué en haut de chaque colonne. Élève absent : écrire « Abs ».
            Inscrire la date de chaque devoir et de la composition sous son titre.
            Fiche à remettre à l'administration pour la saisie dans KalanNet.
        </div>

        <table class="list">
            <thead>
                <tr>
                    <th class="num">N°</th>
                    <th class="matricule">Matricule</th>
                    <th>Nom</th>
                    <th>Prénom(s)</th>
                    @foreach($colonnes as $colonne)
                        <th class="note">{{ $colonne['libelle'] }}<br>/ {{ $bareme($colonne['sur']) }}</th>
                    @endforeach
                    <th class="obs">Observation</th>
                </tr>
                <tr>
                    <th colspan="4" class="date-label">Date</th>
                    @foreach($colonnes as $colonne)
                        <th class="note date-cell"></th>
                    @endforeach
                    <th class="obs"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($fiche['eleves'] as $index => $eleve)
                    <tr>
                        <td class="num">{{ $index + 1 }}</td>
                        <td class="matricule">{{ $eleve->matricule }}</td>
                        <td>{{ $eleve->nom_eleve }}</td>
                        <td>{{ $eleve->prenom_eleve }}</td>
                        @foreach($colonnes as $colonne)
                            <td class="note"></td>
                        @endforeach
                        <td class="obs"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="footer">
            <tr>
                <td>
                    <div>L'enseignant(e) — date et signature</div>
                    <div class="line">&nbsp;</div>
                </td>
                <td>
                    <div>Reçue par l'administration le <span class="blank" style="min-width: 70px"></span></div>
                    <div>Saisie dans KalanNet le <span class="blank" style="min-width: 70px"></span> par <span class="blank" style="min-width: 70px"></span></div>
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
