@php
    $note = [\App\Support\PresentationBulletin::class, 'note'];
    // Notes saisies : « 10 », « 11,75 » ; calculs : deux décimales.
    $brute = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    $coef = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    $titres = ['principales' => 'Matières principales', 'secondaires' => 'Matières secondaires'];
    $lignesTableau = collect($groupes ?? [])->sum(fn ($g) => $g->matieres->count() + 3);
    $periode = !empty($apercu->mois_nom) ? 'Mois de '.$apercu->mois_nom : ($apercu->nom_trimestre ?? '');
@endphp
<div class="feuille a5{{ $lignesTableau > 24 ? ' dense' : '' }}">
    @include('pdf.bulletins._entete_officiel')

    <table class="infos">
        <tr>
            <td class="lib">Année scolaire</td><td class="fort">{{ $anneeScolaire }}</td>
            <td class="adroite fort">{{ ucfirst($periode) }}</td>
        </tr>
        <tr>
            <td class="lib">Classe</td><td class="fort">{{ $apercu->nom_classe }}</td>
            <td class="adroite">Matricule : <strong>{{ $apercu->matricule ?: '-' }}</strong></td>
        </tr>
        <tr>
            <td class="lib">Prénoms</td><td class="fort">{{ $apercu->prenom_eleve }}</td>
            <td class="adroite">@if($lv2)LV2 : <strong>{{ $lv2 }}</strong>@endif</td>
        </tr>
        <tr>
            <td class="lib">Nom</td><td class="fort">{{ mb_strtoupper($apercu->nom_eleve) }}</td><td></td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr><th>Matières</th><th>M.CL</th><th>M.COMP</th><th>M.G</th><th>Coef</th><th>Moy.Coef</th><th>Appr.</th></tr>
        </thead>
        <tbody>
            <tr class="formule"><td></td><td>m</td><td>n</td><td>(m+2n)/3</td><td>k</td><td>(m+2n)/3 × k</td><td></td></tr>
            @foreach($groupes ?? [] as $cle => $groupe)
                <tr class="groupe"><td colspan="7">{{ $titres[$cle] }}</td></tr>
                @foreach($groupe->matieres as $m)
                    <tr>
                        <td class="mat">{{ \App\Support\ArabePdf::texte($m->nom_matiere) }}</td>
                        <td class="n">{{ $brute($m->note_classe) }}</td>
                        <td class="n">{{ $brute($m->note_compo) }}</td>
                        <td class="n">{{ $note($m->M_Gle) }}</td>
                        <td class="c">{{ $coef($m->coef) }}</td>
                        <td class="n">{{ $note($m->M_Coef) }}</td>
                        <td class="appr">{{ $m->appreciation }}</td>
                    </tr>
                @endforeach
                <tr class="partiel">
                    <td>Total partiel</td><td></td><td></td><td></td>
                    <td class="c">{{ $coef($groupe->total_coef) }}</td>
                    <td class="n">{{ $note($groupe->total) }}</td><td></td>
                </tr>
                <tr class="partiel">
                    <td>Moy. partielle</td><td></td><td></td><td></td><td></td>
                    <td class="n">{{ $note($groupe->moyenne) }}</td>
                    <td class="appr">{{ \App\Services\Bulletins\CalculBulletin::appreciation($groupe->moyenne, null) }}</td>
                </tr>
            @endforeach
            <tr class="generale">
                <td>Moy. générale</td><td></td><td></td><td></td>
                <td class="c">{{ $coef($total_coef ?? 0) }}</td>
                <td class="n">{{ $note($moyenne_periode) }}</td>
                <td class="appr">{{ \App\Services\Bulletins\CalculBulletin::appreciation($moyenne_periode, null) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="bas">
        <tr>
            <td style="white-space:nowrap"><span class="lib">Rang :</span> {{ $rangTexte }} / {{ $total_eleves ?: '-' }} élèves classés</td>
            <td style="white-space:nowrap"><span class="lib">Moy. du 1er :</span> {{ $note($statistiques['premier']) }} / 20</td>
        </tr>
    </table>

    <table class="signatures">
        <tr><td>Le parent ou tuteur</td><td class="d">Le directeur / proviseur</td></tr>
    </table>
</div>
