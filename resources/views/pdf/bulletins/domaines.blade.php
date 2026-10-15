@php
    $note = [\App\Support\PresentationBulletin::class, 'note'];
    $ar = [\App\Support\ArabePdf::class, 'texte'];
    $bareme = $surDix ? 10 : 20;
    $parPartie = $domaines->groupBy(fn ($d) => $d->partie ?? 'autre');
    $libellesParties = ['arabe' => ['Partie arabe', 'القسم العربي'], 'francais' => ['Partie française', 'القسم الفرنسي'], 'autre' => ['Autres matières', null]];
    $trimestresAr = [1 => 'الفصل الأول', 2 => 'الفصل الثاني', 3 => 'الفصل الثالث'];
    preg_match('/\d+/', (string) ($apercu->nom_trimestre ?? ''), $numTrimestre);
    $periodeAr = empty($apercu->mois_nom) ? ($trimestresAr[(int) ($numTrimestre[0] ?? 0)] ?? null) : null;
@endphp
@php
    $lignesTableau = $domaines->sum(fn ($d) => $d->matieres->count()) + $domaines->count() + $parPartie->count();
@endphp
<div class="feuille{{ $lignesTableau > 30 ? ' dense' : '' }}">
    @include('pdf.bulletins._entete_officiel')

    <table class="titre">
        <tr>
            <td class="fr">Bulletin de notes<small>{{ $periodeTitre }} · Année scolaire {{ $anneeScolaire }}@if($annee_hegirienne) ({{ $annee_hegirienne }} H)@endif</small></td>
            <td class="ar">{{ $ar('كشف الدرجات') }}@if($periodeAr)<small>{{ $ar($periodeAr) }}</small>@endif</td>
        </tr>
    </table>

    <table class="identite">
        <tr>
            <td style="width:38%"><span class="lib">Prénom et nom · <span class="ar">{{ $ar('الاسم واللقب') }}</span></span><span class="val">{{ $apercu->prenom_eleve }} {{ $apercu->nom_eleve }}</span></td>
            <td><span class="lib">Classe · <span class="ar">{{ $ar('الصف') }}</span></span><span class="val">{{ $apercu->nom_classe }}</span></td>
            <td><span class="lib">Matricule · <span class="ar">{{ $ar('رقم التسجيل') }}</span></span><span class="val">{{ $apercu->matricule ?: '-' }}</span></td>
            <td><span class="lib">Effectif · <span class="ar">{{ $ar('العدد') }}</span></span><span class="val">{{ $total_eleves ?: '-' }}</span></td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th style="text-align:left; padding-left:10px">Matière</th>
                <th style="text-align:right"><span class="ar">{{ $ar('المادة') }}</span></th>
                <th>Classe<span class="ar">{{ $ar('الفصل') }}</span></th>
                <th>Compo<span class="ar">{{ $ar('الامتحان') }}</span></th>
                <th>Moyenne<span class="ar">{{ $ar('المعدل') }}</span></th>
                <th>Coef<span class="ar">{{ $ar('المعامل') }}</span></th>
                <th>Total<span class="ar">{{ $ar('المجموع') }}</span></th>
                <th>Domaine<span class="ar">{{ $ar('معدل المجال') }}</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($parPartie as $partie => $domainesPartie)
                @php [$libFr, $libAr] = $libellesParties[$partie] ?? $libellesParties['autre']; @endphp
                @if($parPartie->count() > 1 || $partie !== 'autre')
                    <tr class="partie">
                        <td colspan="4">{{ $libFr }}</td>
                        <td colspan="4" class="ar">@if($libAr){{ $ar($libAr) }}@endif</td>
                    </tr>
                @endif
                @foreach($domainesPartie as $domaine)
                    <tr class="domaine">
                        <td colspan="2">{{ $domaine->nom }} <span class="dom-appr">· {{ $domaine->appreciation }}</span></td>
                        <td colspan="5" class="dom-ar">@if($domaine->nom_ar){{ $ar($domaine->nom_ar) }}@endif</td>
                        <td class="num dom-moy">{{ $note($domaine->moyenne) }}</td>
                    </tr>
                    @foreach($domaine->matieres as $m)
                        <tr>
                            <td class="mat">{{ $m->nom_ar ? $m->nom_fr : $m->nom_matiere }}</td>
                            <td class="mat-ar">@if($m->nom_ar){{ $ar($m->nom_ar) }}@endif</td>
                            <td class="num">{{ $note($m->note_classe) }}</td>
                            <td class="num">{{ $note($m->note_compo) }}</td>
                            <td class="num moy">{{ $note($m->M_Gle) }}</td>
                            <td class="num">{{ $note($m->coef, $m->coef == (int) $m->coef ? 0 : 2) }}</td>
                            <td class="num">{{ $note($m->M_Coef) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>

    @if(!empty($parties))
        <table class="maitres">
            <tr>
                @foreach(['arabe' => ['Maître de la partie arabe', 'معلم القسم العربي'], 'francais' => ['Maître de la partie française', 'معلم القسم الفرنسي']] as $partie => [$titreFr, $titreAr])
                    <td style="{{ $loop->first ? 'padding-right:2mm' : 'padding-left:2mm' }}">
                        @if(isset($parties[$partie]))
                            <div class="maitre-box">
                                <div class="tete"><table><tr><td>{{ $titreFr }}</td><td class="ar">{{ $ar($titreAr) }}</td></tr></table></div>
                                <div class="corps">
                                    <table>
                                        <tr><td>Moyenne · {{ $ar('المعدل') }}</td><td class="v">{{ $note($parties[$partie]['moyenne']) }} / {{ $bareme }}</td></tr>
                                        <tr><td>Appréciation · {{ $ar('التقدير') }}</td><td class="v appr">{{ $parties[$partie]['appreciation'] }}</td></tr>
                                        <tr><td class="sign" colspan="2">Signature · {{ $ar('التوقيع') }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="synthese">
        <tr>
            <td class="general" style="width:32%"><span class="lib">Moyenne générale · <span class="ar">{{ $ar('المعدل العام') }}</span></span><span class="val">{{ $note($moyenne_periode) }} / {{ $bareme }}</span></td>
            <td><span class="lib">Appréciation · <span class="ar">{{ $ar('التقدير') }}</span></span><span class="val">{{ \App\Services\Bulletins\CalculBulletin::appreciation($moyenne_periode, $ordre) }}</span></td>
            <td><span class="lib">Rang · <span class="ar">{{ $ar('الترتيب') }}</span></span><span class="val">{{ $rangTexte }} / {{ $total_eleves ?: '-' }}</span></td>
            <td style="border-right:none"><span class="lib">Moy. du premier · <span class="ar">{{ $ar('معدل الأول') }}</span></span><span class="val">{{ $note($statistiques['premier']) }}</span></td>
        </tr>
    </table>
    @include('pdf.bulletins._annuel')

    <table class="signatures">
        <tr>
            <td>Le parent ou tuteur<br><span class="ar">{{ $ar('ولي الأمر') }}</span></td>
            <td>Le directeur<br><span class="ar">{{ $ar('المدير') }}</span></td>
        </tr>
    </table>

</div>
