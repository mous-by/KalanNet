@php
    $note = [\App\Support\PresentationBulletin::class, 'note'];
    // La conduite compte comme une matière de coefficient 1, en fin de tableau.
    $lignes = $matieres->map(fn ($m) => (object) [
        'nom' => \App\Support\ArabePdf::texte($m->nom_matiere), 'classe' => $m->note_classe, 'compo' => $m->note_compo,
        'moyenne' => $m->M_Gle, 'coef' => $m->coef, 'total' => $m->M_Coef, 'appreciation' => $m->appreciation,
    ]);
    if ($note_conduite !== null) {
        $lignes->push((object) ['nom' => 'Conduite', 'classe' => null, 'compo' => null, 'moyenne' => $note_conduite, 'coef' => 1, 'total' => $note_conduite, 'appreciation' => $appreciation_conduite]);
    }
    $vides = max(0, 14 - $lignes->count());
@endphp
<div class="feuille">
    <div class="tete-sante">
        @if($logo)<img src="{{ $logo }}" alt="">@endif
        <div class="nom">{{ $nomEcole }}</div>
        @if($sousTitre)<div class="sous">{{ $sousTitre }}</div>@endif
        @if($telephone)<div class="sous">Tél. {{ $telephone }}</div>@endif
        <div class="titre">Bulletin de notes</div>
    </div>

    <table class="infos">
        <tr>
            <td><span class="lib">Année scolaire :</span> <strong>{{ $anneeScolaire }}</strong></td>
            <td><span class="lib">Année d’étude :</span> <strong>{{ $apercu->nom_classe }}</strong></td>
            <td class="d">
                @if(!empty($apercu->mois_nom))
                    <span class="lib">Mois :</span> <strong>{{ $apercu->mois_nom }}</strong>
                @else
                    <span class="lib">Trimestre :</span> <strong>{{ trim(preg_replace('/\s*trimestre\s*/iu', ' ', (string) ($apercu->nom_trimestre ?? ''))) }}</strong>
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2"><span class="lib">Nom de l’étudiant(e) :</span> <strong>{{ $apercu->prenom_eleve }} {{ $apercu->nom_eleve }}</strong></td>
            <td class="d"><span class="lib">Effectif :</span> <strong>{{ $total_eleves ?: '-' }}</strong></td>
        </tr>
        <tr>
            <td><span class="lib">Filière :</span> <strong>{{ $apercu->nom_filiere ?? '-' }}</strong></td>
            <td colspan="2"><span class="lib">N° Matricule :</span> <strong>{{ $apercu->matricule ?: '-' }}</strong></td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th class="g">Matières</th>
                <th>Moy. de classe<br>a</th>
                <th>Notes de compo.<br>b</th>
                <th>Moy. générale<br>(a+2b)/3</th>
                <th>Coeff</th>
                <th>Notes coeffic.</th>
                <th>Appréciations des profs</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $l)
                <tr>
                    <td class="matiere">{{ $l->nom }}</td>
                    <td class="num">{{ $l->classe === null ? '' : $note($l->classe) }}</td>
                    <td class="num">{{ $l->compo === null ? '' : $note($l->compo) }}</td>
                    <td class="num moy">{{ $note($l->moyenne) }}</td>
                    <td class="num">{{ $note($l->coef, $l->coef == (int) $l->coef ? 0 : 2) }}</td>
                    <td class="num">{{ $note($l->total) }}</td>
                    <td class="appr">{{ $l->appreciation }}</td>
                </tr>
            @endforeach
            @for($i = 0; $i < $vides; $i++)
                <tr class="vide"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    <table class="totaux">
        <tr>
            <td class="lib">Total général</td>
            <td class="val">{{ $note($lignes->sum('total')) }}</td>
            <td class="espace"></td>
            <td class="lib">Total coef.</td>
            <td class="val">{{ $note($lignes->sum('coef'), 0) }}</td>
            <td class="espace"></td>
            <td class="lib">Moyenne obtenue</td>
            <td class="val fort">{{ $note($moyenne_periode) }}</td>
        </tr>
        <tr>
            <td class="lib">Moy. du 1<sup>er</sup></td>
            <td class="val">{{ $note($statistiques['premier']) }}</td>
            <td class="espace"></td>
            <td class="lib">Rang</td>
            <td class="val">{{ $rangTexte }}</td>
            <td colspan="3"></td>
        </tr>
    </table>

    <table class="generale">
        <tr>
            <td class="lib">Appréciation générale</td>
            <td class="val">{{ \App\Services\Bulletins\CalculBulletin::appreciation($moyenne_periode, $ordre) }}</td>
        </tr>
    </table>

    @include('pdf.bulletins._annuel', ['marge' => '3mm'])

    <table class="signatures">
        <tr><td>{{ $signataire }}</td><td class="d">Parent / Tuteur</td></tr>
    </table>
</div>
