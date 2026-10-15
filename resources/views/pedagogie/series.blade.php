@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Séries du secondaire</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Séries et tendance</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <p class="mb-0 small" style="max-width: 90ch">
                Sur le dernier bulletin de l'année, la <strong>tendance</strong> indique la série vers laquelle s'oriente l'élève :
                en 10ème, une série de 11ème ; en 11ème, une série de 12ème de sa série. Pour chaque série, KalanNet fait la moyenne
                annuelle de l'élève dans les matières cochées ci-dessous (pondérée par les coefficients) et retient la plus forte.
                @unless($personnalise) Les cases cochées sont la liste de départ proposée par KalanNet. @endunless
            </p>
        </div>
        <div class="card-body">
            @if($matieres->isEmpty())
                <p class="text-muted mb-0">Aucune classe du secondaire général avec des matières dans cette école.</p>
            @else
                <form method="POST" action="{{ route('pedagogie.series.update') }}">
                    @csrf
                    @foreach([11 => 'Vers la 11ème (élèves de 10ème)', 12 => 'Vers la 12ème (élèves de 11ème)'] as $niveau => $titre)
                        <h6 class="fw-bold text-uppercase small text-muted mt-3">{{ $titre }}</h6>
                        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                            @foreach($series as $code => [$libelle, $niveauSerie, $origine])
                                @continue($niveauSerie !== $niveau)
                                <div class="col">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="fw-bold mb-2">{{ $libelle }}@if($origine) <span class="small text-muted fw-normal">· depuis {{ $series[$origine][0] }}</span>@endif</div>
                                        @foreach($matieres as $matiere)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="series[{{ $code }}][]" value="{{ $matiere->id_matiere }}"
                                                    id="s_{{ $code }}_{{ $matiere->id_matiere }}" @checked(in_array((int) $matiere->id_matiere, $choix[$code] ?? [], true)) @disabled(!$peutModifier)>
                                                <label class="form-check-label" for="s_{{ $code }}_{{ $matiere->id_matiere }}">{{ $matiere->nom_matiere }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    @if($peutModifier)
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn px-4 theme-pill-active">Enregistrer</button>
                            @if($personnalise)
                                <button type="submit" name="reinitialiser" value="1" class="btn btn-outline-secondary">Revenir à la liste de départ</button>
                            @endif
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>
@endsection
