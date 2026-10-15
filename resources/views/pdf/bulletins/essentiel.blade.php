@php
    $note = [\App\Support\PresentationBulletin::class, 'note'];
    $bareme = $surDix ? 10 : 20;
    // Secondaire : matières principales puis secondaires, libellé vertical à droite.
    $avecGroupes = !empty($groupes ?? []);
@endphp
<div class="feuille a5">
    @include('pdf.bulletins._entete_officiel')

    <table class="titre">
        <tr>
            <td class="t">{{ $periodeTitre }}</td>
            <td class="a">{{ $anneeScolaire }}</td>
        </tr>
    </table>

    <table class="eleve">
        <tr>
            <td style="width:52%"><span class="lib">Élève</span><span class="val">{{ $apercu->prenom_eleve }} {{ $apercu->nom_eleve }}</span></td>
            <td><span class="lib">Classe</span><span class="val">{{ $apercu->nom_classe }}</span></td>
            <td><span class="lib">Matricule</span><span class="val">{{ $apercu->matricule ?: '-' }}</span></td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th style="text-align:left">Matière</th>
                <th>Moy. / {{ $bareme }}</th>
                @unless($surDix)<th>Coef</th>@endunless
                <th style="text-align:right">Appréciation</th>
                @if($avecGroupes)<th style="width:14px"></th>@endif
            </tr>
        </thead>
        <tbody>
            @if($avecGroupes)
                @foreach($groupes as $cleGroupe => $groupe)
                    @foreach($groupe->matieres as $m)
                        <tr>
                            <td class="mat">{{ !empty($m->est_conduite) ? 'Conduite' : \App\Support\ArabePdf::texte($m->nom_matiere) }}</td>
                            <td class="num moy">{{ $note($m->M_Gle) }}</td>
                            <td class="num">{{ $note($m->coef, $m->coef == (int) $m->coef ? 0 : 2) }}</td>
                            <td class="appr">{{ $m->appreciation }}</td>
                            @if($loop->first)
                                @include('pdf.bulletins._groupe_vertical', ['cle' => $cleGroupe, 'lignes' => $groupe->matieres->count() + 1, 'largeur' => '14px', 'taille' => '7.5px'])
                            @endif
                        </tr>
                    @endforeach
                    <tr style="background:#f1f1f1; font-weight:bold">
                        <td class="mat">Moy. partielle</td>
                        <td class="num">{{ $note($groupe->moyenne) }}</td>
                        <td class="num">{{ $note($groupe->total_coef, 0) }}</td>
                        <td class="appr">{{ \App\Services\Bulletins\CalculBulletin::appreciation($groupe->moyenne, $ordre) }}</td>
                    </tr>
                @endforeach
            @else
            @foreach($matieres as $m)
                <tr>
                    <td class="mat">{{ \App\Support\ArabePdf::texte($m->nom_matiere) }}</td>
                    <td class="num moy">{{ $note($m->M_Gle) }}</td>
                    @unless($surDix)<td class="num">{{ $note($m->coef, $m->coef == (int) $m->coef ? 0 : 2) }}</td>@endunless
                    <td class="appr">{{ $m->appreciation }}</td>
                </tr>
            @endforeach
            @if($note_conduite !== null)
                <tr>
                    <td class="mat">Conduite</td>
                    <td class="num moy">{{ $note($note_conduite) }}</td>
                    <td class="num">1</td>
                    <td class="appr">{{ $appreciation_conduite }}</td>
                </tr>
            @endif
            @endif
        </tbody>
    </table>

    <table class="synthese">
        <tr>
            <td class="general" style="width:40%">
                <span class="lib">Moyenne générale</span>
                <span class="val">{{ $note($moyenne_periode) }} / {{ $bareme }}</span>
                <span class="mention">{{ \App\Services\Bulletins\CalculBulletin::appreciation($moyenne_periode, $ordre) }}</span>
            </td>
            <td><span class="lib">Rang</span><span class="val">{{ $rangTexte }} / {{ $total_eleves ?: '-' }}</span></td>
            <td><span class="lib">Premier</span><span class="val">{{ $note($statistiques['premier']) }}</span></td>
        </tr>
    </table>
    @include('pdf.bulletins._annuel', ['taille' => '9px'])

    <table class="signatures">
        <tr>
            <td>Le parent ou tuteur</td>
            <td class="d">Le directeur</td>
        </tr>
    </table>
</div>
