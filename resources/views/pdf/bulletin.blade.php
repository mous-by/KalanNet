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
        @include('pdf.bulletins.styles.'.$modele)
    </style>
</head>
<body>
@foreach($bulletins as $bulletin)
    <div class="page">
        @include('pdf.bulletins.'.$modele, \App\Support\PresentationBulletin::variables($bulletin))
    </div>
@endforeach
</body>
</html>
