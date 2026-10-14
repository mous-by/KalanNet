@php
    $note = [\App\Support\PresentationBulletin::class, 'note'];
    $ar = [\App\Support\ArabePdf::class, 'texte'];
    $bareme = $surDix ? 10 : 20;
    $parPartie = $domaines->groupBy(fn ($d) => $d->partie ?? 'autre');
    $libellesParties = ['arabe' => ['Partie arabe', 'القسم العربي'], 'francais' => ['Partie française', 'القسم الفرنسي'], 'autre' => ['Autres matières', null]];
@endphp
<div class="feuille a5">
    @include('pdf.bulletins._entete_officiel')

    <table class="bande">
        <tr>
            <td class="t">{{ $periodeTitre }}</td>
            <td class="ar">{{ $ar('كشف الدرجات') }}</td>
            <td class="a">{{ $anneeScolaire }}@if($annee_hegirienne) ({{ $annee_hegirienne }} H)@endif</td>
        </tr>
    </table>

    <table class="eleve">
        <tr>
            <td class="photo">
                @if($photo)<img src="{{ $photo }}" alt="">@else<div class="initiales">{{ mb_strtoupper(mb_substr((string) $apercu->prenom_eleve, 0, 1).mb_substr((string) $apercu->nom_eleve, 0, 1)) }}</div>@endif
            </td>
            <td style="width:44%"><span class="lib">Élève</span><span class="val">{{ $apercu->prenom_eleve }} {{ $apercu->nom_eleve }}</span></td>
            <td><span class="lib">Classe</span><span class="val">{{ $apercu->nom_classe }}</span></td>
            <td><span class="lib">Matricule</span><span class="val">{{ $apercu->matricule ?: '-' }}</span></td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th style="text-align:left">Domaine / matière</th>
                <th></th>
                <th>Cl.</th><th>Co.</th><th>Moy.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($parPartie as $partie => $domainesPartie)
                @php [$libFr, $libAr] = $libellesParties[$partie] ?? $libellesParties['autre']; @endphp
                @if($parPartie->count() > 1 || $partie !== 'autre')
                    <tr class="partie"><td colspan="2">{{ $libFr }}</td><td colspan="3" class="ar">@if($libAr){{ $ar($libAr) }}@endif</td></tr>
                @endif
                @foreach($domainesPartie as $domaine)
                    <tr class="domaine">
                        <td>{{ $domaine->nom }}</td>
                        <td colspan="3" class="ar">@if($domaine->nom_ar){{ $ar($domaine->nom_ar) }}@endif</td>
                        <td class="num moy">{{ $note($domaine->moyenne) }}</td>
                    </tr>
                    @foreach($domaine->matieres as $m)
                        <tr>
                            <td class="mat">{{ $m->nom_fr ?: $m->nom_matiere }}</td>
                            <td class="mat-ar">@if($m->nom_ar){{ $ar($m->nom_ar) }}@endif</td>
                            <td class="num">{{ $note($m->note_classe) }}</td>
                            <td class="num">{{ $note($m->note_compo) }}</td>
                            <td class="num">{{ $note($m->M_Gle) }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if(!empty($parties))
        <table class="maitres">
            <tr>
                @foreach(['arabe' => 'Maître arabe', 'francais' => 'Maître de français'] as $partie => $titre)
                    <td class="box" style="{{ $loop->first ? 'border-right:none' : '' }}">
                        @if(isset($parties[$partie]))
                            <div class="lib">{{ $titre }} · {{ $note($parties[$partie]['moyenne']) }} / {{ $bareme }}</div>
                            <div class="appr">{{ $parties[$partie]['appreciation'] }}</div>
                            <div class="sign"></div>
                        @endif
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="synthese">
        <tr>
            <td class="general" style="width:36%"><span class="lib">Moyenne générale</span><span class="val">{{ $note($moyenne_periode) }} / {{ $bareme }}</span></td>
            <td><span class="lib">Appréciation</span><span class="val" style="font-size:8.5px">{{ \App\Services\Bulletins\CalculBulletin::appreciation($moyenne_periode, $ordre) }}</span></td>
            <td><span class="lib">Rang</span><span class="val">{{ $rangTexte }} / {{ $total_eleves ?: '-' }}</span></td>
            <td style="border-right:none"><span class="lib">Premier</span><span class="val">{{ $note($statistiques['premier']) }}</span></td>
        </tr>
    </table>

    <table class="signatures">
        <tr><td>Le parent · {{ $ar('ولي الأمر') }}</td><td class="d">Le directeur · {{ $ar('المدير') }}</td></tr>
    </table>
</div>
