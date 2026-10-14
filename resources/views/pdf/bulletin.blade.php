@php
    $premier = $bulletins->first();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $bulletins->count() === 1 ? __('bulletins.pdf_title_single', ['nom' => $premier['apercu']->nom_eleve]) : __('bulletins.pdf_title_classe', ['classe' => $premier['apercu']->nom_classe]) }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; font-family: 'DejaVu Sans', sans-serif; color: #1d2433; }
        .page { page-break-after: always; position: relative; }
        .page:last-child { page-break-after: auto; }
        .ar { font-family: 'DejaVu Sans', sans-serif; }
        .num { text-align: center; white-space: nowrap; }
        table { border-collapse: collapse; }
        .deux-exemplaires { width: 100%; table-layout: fixed; }
        .deux-exemplaires td.exemplaire { width: 50%; vertical-align: top; padding: 0; }
        .deux-exemplaires td.gauche { padding-right: 8px; }
        .deux-exemplaires td.droite { padding-left: 8px; border-left: 1px dashed #000; }
        @include('pdf.bulletins.styles.'.$modele)
    </style>
</head>
<body>
@foreach($bulletins as $bulletin)
    @php $variables = \App\Support\PresentationBulletin::variables($bulletin); @endphp
    <div class="page">
        @if(\App\Support\ModelesBulletin::deuxExemplaires($modele))
            {{-- A4 paysage : deux exemplaires A5 du même bulletin (parents / école), séparés par un trait de coupe. --}}
            <table class="deux-exemplaires">
                <tr>
                    <td class="exemplaire gauche">@include('pdf.bulletins.'.$modele, $variables)</td>
                    <td class="exemplaire droite">@include('pdf.bulletins.'.$modele, $variables)</td>
                </tr>
            </table>
        @else
            @include('pdf.bulletins.'.$modele, $variables)
        @endif
    </div>
@endforeach
</body>
</html>
