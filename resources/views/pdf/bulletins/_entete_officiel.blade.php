<table class="entete" style="width:100%">
    <tr>
        <td class="officiel">
            @if($enteteGauche)
                <strong>{{ $enteteGauche }}</strong><br><span class="etoiles">* * * * *</span><br>
            @endif
            @if($academie){{ $academie }}<br>@endif
            @if($capLabel)<strong>{{ $capLabel }}</strong>@endif
        </td>
        <td class="logo">
            @if($logo)<img src="{{ $logo }}" alt="">@endif
        </td>
        <td class="officiel">
            @if($enteteDroite)<strong>{!! nl2br(e($enteteDroite)) !!}</strong>@endif
        </td>
    </tr>
</table>
<div class="ecole">
    <div class="nom">{{ $nomEcole }}</div>
    @if($sousTitre)<div class="sous">{{ $sousTitre }}</div>@endif
    @if($telephone)<div class="tel">Tél. {{ $telephone }}</div>@endif
</div>
