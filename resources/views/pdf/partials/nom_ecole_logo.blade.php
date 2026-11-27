{{-- Nom de l'école, avec son logo à gauche s'il existe ; attend $ecole (peut être null). --}}
@php
    $logoEcoleDoc = \App\Support\LogoEcole::dataUri($ecole ?? null);
@endphp
<table style="width: 100%; border-collapse: collapse; margin: 6px 0 2px;">
    <tr>
        <td style="width: 70px; vertical-align: middle;">
            @if($logoEcoleDoc)
                <img src="{{ $logoEcoleDoc }}" alt="" style="max-width: 62px; max-height: 62px;">
            @endif
        </td>
        <td class="school-name" style="vertical-align: middle; margin: 0;">{{ $ecole?->nomEcole ?? '' }}</td>
        <td style="width: 70px;"></td>
    </tr>
</table>
