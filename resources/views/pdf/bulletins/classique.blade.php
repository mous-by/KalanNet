@php
    $note = [\App\Support\PresentationBulletin::class, 'note'];
    $bareme = $surDix ? 10 : 20;
@endphp
<div class="feuille">
    @include('pdf.bulletins._entete_officiel')

    <table class="bandeau">
        <tr>
            <td class="titre">{{ $periodeTitre }}</td>
            <td class="annee">Année scolaire <strong>{{ $anneeScolaire }}</strong></td>
        </tr>
    </table>

    <table class="identite">
        <tr>
            <td style="width:40%"><span class="lib">Nom et prénom</span><span class="val">{{ $apercu->prenom_eleve }} {{ $apercu->nom_eleve }}</span></td>
            <td><span class="lib">Matricule</span><span class="val">{{ $apercu->matricule ?: '-' }}</span></td>
            <td><span class="lib">Classe</span><span class="val">{{ $apercu->nom_classe }}</span></td>
            <td><span class="lib">Sexe</span><span class="val">{{ $apercu->genre_eleve ?: '-' }}</span></td>
            <td style="border-right:none"><span class="lib">Effectif classé</span><span class="val">{{ $total_eleves ?: '-' }}</span></td>
        </tr>
    </table>

    <table class="notes">
        @if($surDix)
            <thead>
                <tr><th style="text-align:left">Matière</th><th>Note / 10</th><th>Appréciation</th></tr>
            </thead>
            <tbody>
                @foreach($matieres as $m)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td class="matiere">{{ \App\Support\ArabePdf::texte($m->nom_matiere) }}</td>
                        <td class="num moy">{{ $note($m->M_Gle) }}</td>
                        <td class="num appr">{{ $m->appreciation }}</td>
                    </tr>
                @endforeach
                <tr class="total"><td>Total</td><td class="num">{{ $note($matieres->sum('M_Gle')) }}</td><td></td></tr>
            </tbody>
        @else
            <thead>
                <tr>
                    <th style="text-align:left">Matière</th>
                    <th>Classe</th><th>Compo</th><th>Moyenne</th><th>Coef</th><th>Total</th><th>Appréciation</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bareme"><td></td><td class="num">/ 20</td><td class="num">/ 20</td><td class="num">(cl. + 2 × co.) / 3</td><td class="num">k</td><td class="num">moy. × k</td><td></td></tr>
                @foreach($matieres as $m)
                    <tr class="{{ $loop->even ? 'alt' : '' }}">
                        <td class="matiere">{{ \App\Support\ArabePdf::texte($m->nom_matiere) }}</td>
                        <td class="num">{{ $note($m->note_classe) }}</td>
                        <td class="num">{{ $note($m->note_compo) }}</td>
                        <td class="num moy">{{ $note($m->M_Gle) }}</td>
                        <td class="num">{{ $note($m->coef, $m->coef == (int) $m->coef ? 0 : 2) }}</td>
                        <td class="num">{{ $note($m->M_Coef) }}</td>
                        <td class="num appr">{{ $m->appreciation }}</td>
                    </tr>
                @endforeach
                @if($note_conduite !== null)
                    <tr class="{{ $matieres->count() % 2 ? 'alt' : '' }}">
                        <td class="matiere">Conduite</td><td></td><td></td>
                        <td class="num moy">{{ $note($note_conduite) }}</td><td class="num">1</td>
                        <td class="num">{{ $note($note_conduite) }}</td><td class="num appr">{{ $appreciation_conduite }}</td>
                    </tr>
                @endif
                <tr class="total">
                    <td>Total</td><td></td><td></td><td></td>
                    <td class="num">{{ $note($matieres->sum('coef') + ($note_conduite !== null ? 1 : 0), 0) }}</td>
                    <td class="num">{{ $note($matieres->sum('M_Coef') + ($note_conduite ?? 0)) }}</td>
                    <td></td>
                </tr>
            </tbody>
        @endif
    </table>

    <table class="bas">
        <tr>
            <td style="width:38%; padding-right:4mm">
                <div class="resultat">
                    <div class="tete">Résultats de la période</div>
                    <div class="grand"><span class="val">{{ $note($moyenne_periode) }}</span> <span class="sur">/ {{ $bareme }}</span></div>
                    <div class="mention">{{ \App\Services\Bulletins\CalculBulletin::appreciation($moyenne_periode, $ordre) }}</div>
                    <table>
                        <tr class="ligne"><td>Rang</td><td class="v">{{ $rangTexte }} / {{ $total_eleves ?: '-' }}</td></tr>
                        <tr class="ligne"><td>Moyenne du premier</td><td class="v">{{ $note($statistiques['premier']) }}</td></tr>
                        <tr class="ligne"><td>Moyenne la plus faible</td><td class="v">{{ $note($statistiques['dernier']) }}</td></tr>
                        <tr class="ligne"><td>Moyenne de la classe</td><td class="v">{{ $note($statistiques['classe']) }}</td></tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="cadre">
                    <div class="tete">Observations du conseil des maîtres</div>
                    <div class="corps">
                        <div class="pointilles"></div>
                        <div class="pointilles"></div>
                        <table class="decisions" style="width:100%; margin-top:6px">
                            <tr>
                                <td><span class="case"></span>Tableau d'honneur</td>
                                <td><span class="case"></span>Félicitations</td>
                            </tr>
                            <tr>
                                <td><span class="case"></span>Encouragements</td>
                                <td><span class="case"></span>Avertissement</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>Le maître / professeur principal</td>
            <td>Le parent ou tuteur</td>
            <td>Le directeur</td>
        </tr>
    </table>

    <div class="pied">{{ $nomEcole }} · {{ $apercu->nom_classe }} · {{ $periodeTitre }} {{ $anneeScolaire }}</div>
</div>
