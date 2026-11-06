@php $colonnes = $enseignants ? 21 : 18; @endphp
<table class="grille personnes">
    <tr>
        <th rowspan="2" style="width: 2.5%;">N°</th><th rowspan="2" style="width: {{ $enseignants ? 12 : 15 }}%;">Prénom &amp; Nom</th><th rowspan="2" style="width: 6%;">Date nais.</th>
        <th rowspan="2" style="width: 9%;">N° Mle</th><th rowspan="2" style="width: 5%;">Classe / échelon</th><th rowspan="2" style="width: 4.5%;">S.F</th><th rowspan="2" style="width: 5%;">Fonct.</th>
        @if($enseignants)<th rowspan="2" style="width: 6%;">Spécialité</th><th rowspan="2" style="width: 7.5%;">{{ $jardin ? 'Sections tenues' : 'Classes tenues' }}</th>@endif
        <th rowspan="2" style="width: 4.5%;">Diplôme</th><th colspan="6">Date</th><th rowspan="2" style="width: 5%;">Contact</th>
    </tr>
    <tr style="font-size: 8px;">
        <th style="width: 5.5%;">Recru-tement</th><th style="width: 5.5%;">Intégration fonct. publ.</th><th style="width: 5.5%;">Titula-risation</th><th style="width: 5.5%;">Arrivée CAP</th><th style="width: 5.5%;">Dernier poste</th><th style="width: 5.5%;">Arrivée {{ $jardin ? 'au CDPE' : "à l'école" }}</th>
    </tr>
    @php $lignes = max(count($personnes), $enseignants ? 1 : 4); @endphp
    @foreach(range(0, $lignes - 1) as $i)
        @php $p = $personnes[$i] ?? []; @endphp
        <tr>
            <td style="text-align: center;">{{ $i + 1 }}</td>
            <td class="{{ !empty($p['femme']) ? 'femme' : '' }}">{{ $p['nom'] ?? '' }}</td>
            <td>{{ $p['naissance'] ?? '' }}</td><td style="font-size: 8px;">{{ $p['matricule'] ?? '' }}</td><td>{{ $p['echelon'] ?? '' }}</td><td>{{ $p['sf'] ?? '' }}</td><td>{{ $p['fonction'] ?? '' }}</td>
            @if($enseignants)<td>{{ $p['specialite'] ?? '' }}</td><td>{{ $p['classes'] ?? '' }}</td>@endif
            <td>{{ $p['diplome'] ?? '' }}</td>
            @foreach(['recrutement', 'integration', 'titularisation', 'arrivee_cap', 'dernier_poste', 'arrivee_ecole'] as $date)<td>{{ $p[$date] ?? '' }}</td>@endforeach
            <td>{{ $p['contact'] ?? '' }}</td>
        </tr>
    @endforeach
</table>
