{{-- Bulletin de fin d'année : moyenne et rang annuels (moyenne des périodes composées). --}}
@if(!empty($annuel))
    @php
        $rangAnnuel = $annuel['rang'].($annuel['rang'] === 1 ? 'er' : 'e');
        $sur = ($surDix ?? false) ? 10 : 20;
    @endphp
    <table style="width:100%; margin-top:{{ $marge ?? '2mm' }}; border:1.5px solid #000; border-collapse:collapse;">
        <tr>
            <td style="padding:3px 6px; font-size:8px; font-weight:bold; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap;">Fin d'année · {{ $annuel['libelle'] }}</td>
            <td style="padding:3px 6px; text-align:right; font-size:{{ $taille ?? '10px' }}; white-space:nowrap;">
                Moyenne annuelle : <strong>{{ \App\Support\PresentationBulletin::note($annuel['moyenne']) }} / {{ $sur }}</strong>
                &nbsp;&nbsp; Rang annuel : <strong>{{ $rangAnnuel }} / {{ $annuel['total'] }}</strong>
            </td>
        </tr>
        @if(!empty($tendance))
            @php
                $retenue = collect($tendance['retenues'])->pluck('libelle')->implode(' ou ');
                $detail = collect($tendance['retenues'][0]['matieres'])->map(fn ($m, $nom) => \App\Support\ArabePdf::texte($nom).' '.\App\Support\PresentationBulletin::note($m))->implode(' · ');
            @endphp
            <tr>
                <td colspan="2" style="padding:3px 6px; border-top:1px solid #000; font-size:{{ $taille ?? '10px' }};">
                    <strong>Tendance :</strong> {{ $retenue }} <span style="font-size:85%">({{ $detail }})</span>
                </td>
            </tr>
        @endif
    </table>
@endif
