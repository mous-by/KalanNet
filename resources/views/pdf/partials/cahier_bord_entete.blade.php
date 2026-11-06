@php
    // « AE de Kayes », « CAP de Kayes RG » : le modèle écrit déjà « Académie d'enseignement de », « CAP de ».
    $sansPrefixe = fn ($nom) => trim(preg_replace("/^\s*(ae|a\.e\.?|acad[ée]mie( d'enseignement)?|cap|c\.a\.p\.?|centre d'animation p[ée]dagogique)\s+(de |d'|du )?/iu", '', (string) $nom));
@endphp
<table class="entete">
    <tr>
        <td style="width: 50%;">MINISTÈRE DE L'ÉDUCATION NATIONALE<br><span style="padding-left: 50px;">**********</span></td>
        <td style="width: 50%; text-align: right;">RÉPUBLIQUE DU MALI<br>Un Peuple - Un But - Une Foi</td>
    </tr>
    <tr>
        <td>ACADÉMIE D'ENSEIGNEMENT DE {{ Str::upper($sansPrefixe($d['academie'] ?? '')) }}<br>CENTRE D'ANIMATION PÉDAGOGIQUE DE {{ Str::upper($sansPrefixe($d['cap'] ?? '')) }}<br>Commune de : {{ $d['commune'] ?? '' }}</td>
        <td>
            {{ $jardin ? "Jardin d'enfants de" : 'École fondamentale de' }} : <b>{{ $d['ecole'] ?? '' }}</b><br>
            {{ $jardin ? 'Dirigé par' : 'Dirigée par' }} : <b>{{ $d['directeur'] ?? '' }}</b> &nbsp; Contact : <b>{{ $d['contact'] ?? '' }}</b><br>
            Depuis : <b>{{ $d['depuis'] ?? '' }}</b> &nbsp; Régime de recrutement : <b>{{ $regime }}</b>
        </td>
    </tr>
</table>
<div class="titre">ANNÉE SCOLAIRE : {{ $annee }} ({{ $jardin ? "Jardin d'enfants" : ($cycle === 'premier' ? '1° Cycle' : '2° Cycle') }})</div>
