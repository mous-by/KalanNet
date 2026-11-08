{{--
    Chaque ordre ne voit que ses rapports : le jardin et le fondamental leur rapport succinct (CAP), le secondaire son
    rapport succinct, son rapport de rentrée et son rapport de fin d'année (académie). Rien à choisir s'il n'y en a qu'un.
--}}
@php
    $portees = \App\Support\RapportSuccinctDonnees::portees((int) session('idEcole'));
    $onglets = [];
    if (in_array('fondamental', $portees, true)) {
        $onglets['fondamental'] = [in_array('secondaire', $portees, true) ? 'Rapport succinct (CAP)' : 'Rapport succinct', route('rapport-succinct.index', ['annee' => $annee->id_anneeScolaire, 'portee' => 'fondamental'])];
    }
    if (in_array('secondaire', $portees, true)) {
        $onglets['secondaire'] = [in_array('fondamental', $portees, true) ? 'Rapport succinct (Académie)' : 'Rapport succinct', route('rapport-succinct.index', ['annee' => $annee->id_anneeScolaire, 'portee' => 'secondaire'])];
        $onglets['rentree'] = ['Rapport de rentrée', route('rapport-secondaire.index', ['type' => 'rentree', 'annee' => $annee->id_anneeScolaire])];
        $onglets['fin'] = ["Rapport de fin d'année", route('rapport-secondaire.index', ['type' => 'fin', 'annee' => $annee->id_anneeScolaire])];
    }
@endphp
@if(count($onglets) > 1)
    <ul class="nav nav-tabs mb-3">
        @foreach($onglets as $cle => [$libelle, $lien])
            <li class="nav-item">
                <a class="nav-link fw-semibold {{ $actif === $cle ? 'active' : '' }}" @if($actif === $cle) aria-current="page" @endif href="{{ $lien }}">{{ $libelle }}</a>
            </li>
        @endforeach
    </ul>
@endif
