{{--
    En-tête officiel d'un document d'école (pays, académie, CAP) ; attend $ecole (peut être null) et, s'il est connu,
    $classe. Le CAP n'apparaît que pour les cycles qui en relèvent (jardin, Fondamental I et II) : le secondaire
    général, technique et professionnel dépend directement de l'Académie.
--}}
@php
    $ordreEntete = $classe->ordreEnseignement ?? null;
    $avecCap = blank($ordreEntete) || \App\Support\Ordres::sousCap($ordreEntete);
@endphp
<table class="header">
    <tr>
        <td width="50%">
            @if($ecole?->pays?->entete_document_gauche)
                <strong>{{ $ecole->pays->entete_document_gauche }}</strong><br>
            @endif
            Académie d'Enseignement de {{ \App\Support\NomAcademie::sansPrefixe($ecole?->academieRef?->nom_academie ?? $ecole?->academie) }}
            @if($avecCap)
                <br>CAP de {{ \App\Support\NomAcademie::capSansPrefixe($ecole?->cap) }}
            @endif
        </td>
        <td width="50%" class="text-right">
            @if($ecole?->pays?->entete_document_droite)
                <strong>{!! nl2br(e($ecole->pays->entete_document_droite)) !!}</strong>
            @endif
        </td>
    </tr>
</table>
