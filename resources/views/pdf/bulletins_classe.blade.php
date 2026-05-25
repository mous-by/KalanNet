<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletins - {{ $classe->nom_classe }}</title>
    <style>
        @page { margin: 8px; }
        body { font-family: Arial, DejaVu Sans, sans-serif; font-size: 10px; margin: 0; padding: 0; color: #000; }
        .bulletin-page { page-break-after: always; }
        .bulletin-page:last-child { page-break-after: auto; }
        .bulletin-container { width: 100%; }
        .header-table { width: 100%; font-size: 9px; margin-bottom: 6px; border-collapse: collapse; }
        .header-table td { vertical-align: top; width: 50%; padding: 0; }
        .left-content, .right-content { text-align: center; line-height: 1.15; }
        .cap-texte { margin-top: 2px; font-weight: bold; font-size: 9px; line-height: 1.1; }
        .school-table { width: auto; margin: 0 auto 3px; border-collapse: collapse; text-align: center; }
        .logo-cell { width: 56px; vertical-align: middle; text-align: center; }
        .school-logo { width: 46px; height: 46px; border-radius: 50%; object-fit: cover; }
        .school-name-cell { vertical-align: middle; text-align: center; }
        .school-name-cell h4, .sub-school-name { font-weight: bold; text-decoration: underline; text-transform: uppercase; margin: 0; font-size: 12px; }
        .school-phone { font-weight: bold; text-align: center; text-decoration: underline; text-transform: uppercase; margin: 0; }
        .period-title { text-align: center; margin-top: 7px; font-weight: bold; text-decoration: underline; text-transform: uppercase; }
        .identity-table { border: 4px double #000; border-collapse: collapse; width: 100%; margin-top: 8px; text-align: center; font-size: 10px; }
        .identity-table td { border: 2px double #000; padding: 4px 6px; }
        .notes-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9px; }
        .notes-table th, .notes-table td { border: .5px solid #000; padding: 3px 4px; text-align: center; vertical-align: middle; }
        .notes-table th { background: #f1f1f1; font-weight: bold; }
        .text-left { text-align: left !important; }
        .formula-row { font-size: 8px; }
        .total-row { font-weight: bold; }
        .summary-table { width: 100%; font-size: 10px; border-collapse: separate; border-spacing: 8px 3px; margin-top: 7px; font-weight: bold; text-decoration: underline; }
        .summary-table td { text-align: center; white-space: nowrap; }
        .signature-table { width: 100%; font-size: 10px; border-collapse: separate; border-spacing: 12px 4px; margin-top: 14px; font-weight: bold; text-decoration: underline; }
        .signature-table td { width: 50%; }
        .signature-table td:last-child { text-align: right; }
    </style>
</head>
<body>
@foreach($bulletins as $bulletin)
    @php
        $apercu = $bulletin['apercu'];
        $ecole = $bulletin['ecole'];
        $matieres = $bulletin['matieres'];
        $moyenne_periode = $bulletin['moyenne_periode'];
        $moyenne_premier = $bulletin['moyenne_premier'];
        $note_conduite = $bulletin['note_conduite'];
        $appreciation_conduite = $bulletin['appreciation_conduite'];
        $rang = $bulletin['rang'];
        $total_eleves = $bulletin['total_eleves'];
        $ordre = $bulletin['ordre'];
    @endphp
    <div class="bulletin-page">
        @include('pdf.partials.bulletin_alliance')
    </div>
@endforeach
</body>
</html>
