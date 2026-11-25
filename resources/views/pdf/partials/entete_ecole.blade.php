{{-- En-tête officiel d'un document d'école (pays, académie, CAP) ; attend $ecole (peut être null). --}}
<table class="header">
    <tr>
        <td width="50%">
            @if($ecole?->pays?->entete_document_gauche)
                <strong>{{ $ecole->pays->entete_document_gauche }}</strong><br>
            @endif
            Académie d'Enseignement de {{ \App\Support\NomAcademie::sansPrefixe($ecole?->academieRef?->nom_academie ?? $ecole?->academie) }}<br>
            CAP de {{ $ecole?->cap ?? '' }}
        </td>
        <td width="50%" class="text-right">
            @if($ecole?->pays?->entete_document_droite)
                <strong>{!! nl2br(e($ecole->pays->entete_document_droite)) !!}</strong>
            @endif
        </td>
    </tr>
</table>
