{{-- Établissement avec plusieurs ordres : une fiche pour le CAP, une pour l'académie. --}}
<ul class="nav nav-tabs mb-3">
    @foreach(['fondamental' => ['Fondamental (CAP)', 'bi-mortarboard'], 'secondaire' => ['Secondaire (Académie)', 'bi-building']] as $cle => [$libelle, $icone])
        <li class="nav-item">
            <a class="nav-link fw-semibold {{ $portee === $cle ? 'active' : '' }}" @if($portee === $cle) aria-current="page" @endif href="{{ route('rapport-succinct.index', ['annee' => $annee->id_anneeScolaire, 'portee' => $cle]) }}"><i class="bi {{ $icone }} me-1"></i>{{ $libelle }}</a>
        </li>
    @endforeach
</ul>
