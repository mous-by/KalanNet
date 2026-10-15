{{-- Libellé vertical d'un groupe de matières (secondaire), fusionné sur toutes ses lignes. --}}
@php
    $libellesGroupe = ['principales' => ['Matières principales', 'Principales', 'Princ.'], 'secondaires' => ['Matières secondaires', 'Secondaires', 'Sec.']];
    [$libelleLong, $libelleMoyen, $libelleCourt] = $libellesGroupe[$cle];
    $texteGroupe = $lignes >= 7 ? $libelleLong : ($lignes >= 4 ? $libelleMoyen : $libelleCourt);
    $largeurGroupe = $largeur ?? '16px';
@endphp
<td rowspan="{{ $lignes }}" style="width:{{ $largeurGroupe }}; border:1px solid #000; vertical-align:middle; text-align:center; padding:0;">
    <div style="transform:rotate(-90deg); white-space:nowrap; font-weight:bold; font-size:{{ $taille ?? '8px' }}; width:{{ $largeurGroupe }};">{{ $texteGroupe }}</div>
</td>
