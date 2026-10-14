@php
    $note = [\App\Support\PresentationBulletin::class, 'note'];
    $bareme = $surDix ? 10 : 20;
    $niveau = function ($valeur) use ($bareme) {
        $ratio = (float) $valeur / $bareme;
        return match (true) {
            $ratio >= 0.7 => 'n-haut',
            $ratio >= 0.6 => 'n-moyen',
            $ratio >= 0.5 => 'n-juste',
            default => 'n-bas',
        };
    };
    $initiales = mb_strtoupper(mb_substr((string) $apercu->prenom_eleve, 0, 1).mb_substr((string) $apercu->nom_eleve, 0, 1));
    $evolution = $evolution ?? null;
    $ecart = $evolution !== null ? round($moyenne_periode - $evolution, 2) : null;
    $lignes = $matieres->map(fn ($m) => ['nom' => \App\Support\ArabePdf::texte($m->nom_matiere), 'valeur' => $m->M_Gle, 'appreciation' => $m->appreciation]);
    if ($note_conduite !== null) {
        $lignes->push(['nom' => 'Conduite', 'valeur' => $note_conduite, 'appreciation' => $appreciation_conduite]);
    }
@endphp
<div class="bandeau">
    <table>
        <tr>
            @if($logo)<td class="logo"><div><img src="{{ $logo }}" alt=""></div></td>@endif
            <td>
                <div class="nom">{{ $nomEcole }}</div>
                <div class="sous">{{ $academie }}@if($capLabel) · {{ $capLabel }}@endif</div>
            </td>
            <td class="periode"><strong>{{ $periodeTitre }}</strong>{{ $anneeScolaire }}</td>
        </tr>
    </table>
</div>
<div class="feuille">
    <table class="carte">
        <tr>
            <td class="photo">
                @if($photo)<img src="{{ $photo }}" alt="">@else<div class="initiales">{{ $initiales }}</div>@endif
            </td>
            <td class="eleve">
                <div class="n">{{ $apercu->prenom_eleve }} {{ $apercu->nom_eleve }}</div>
                <div class="d">{{ $apercu->nom_classe }} · Matricule {{ $apercu->matricule ?: '-' }}</div>
            </td>
            <td class="score">
                <div><span class="v">{{ $note($moyenne_periode) }}</span><span class="s"> / {{ $bareme }}</span></div>
                <div class="r">Rang {{ $rangTexte }} sur {{ $total_eleves ?: '-' }}</div>
            </td>
        </tr>
    </table>

    @if($ecart !== null)
        <div class="evolution {{ $ecart > 0 ? 'hausse' : ($ecart < 0 ? 'baisse' : 'stable') }}">
            @if($ecart > 0)
                &#9650; En progrès : +{{ $note($ecart) }} point(s) depuis la période précédente ({{ $note($evolution) }}).
            @elseif($ecart < 0)
                &#9660; En baisse : {{ $note($ecart) }} point(s) depuis la période précédente ({{ $note($evolution) }}).
            @else
                Même moyenne qu'à la période précédente.
            @endif
        </div>
    @endif

    <table class="matieres">
        <thead><tr><th>Matière</th><th>Niveau</th><th style="text-align:right">/ {{ $bareme }}</th><th style="text-align:right">Appréciation</th></tr></thead>
        <tbody>
            @foreach($lignes as $l)
                @php $pct = max(0, min(100, (float) $l['valeur'] / $bareme * 100)); @endphp
                <tr>
                    <td class="nom">{{ $l['nom'] }}</td>
                    <td class="barre"><div class="piste">@if($pct > 0)<div class="niveau {{ $niveau($l['valeur']) }}" style="width: {{ number_format($pct, 1, '.', '') }}%"></div>@endif</div></td>
                    <td class="v">{{ $note($l['valeur']) }}</td>
                    <td class="a">{{ $l['appreciation'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="legende">
        <span class="p n-haut"></span>Très bien / Bien <span class="p n-moyen"></span>Assez bien <span class="p n-juste"></span>Passable <span class="p n-bas"></span>À renforcer
    </div>

    <table class="bas">
        <tr>
            <td>
                <div class="avis">
                    <div class="lib">Appréciation générale</div>
                    <div class="t">{{ \App\Services\Bulletins\CalculBulletin::appreciation($moyenne_periode, $ordre) }}</div>
                    <div class="p">Moyenne du premier : {{ $note($statistiques['premier']) }} · moyenne de la classe : {{ $note($statistiques['classe']) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="signatures">
        <tr><td>Le parent ou tuteur</td><td class="d">Le directeur</td></tr>
    </table>
</div>
