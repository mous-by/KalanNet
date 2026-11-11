@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Rapports du secondaire</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Séries de mes classes</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert alert-info border-0 border-start border-4">
        <i class="bi bi-info-circle me-1"></i>
        Choisissez la série de chaque classe du secondaire général. Les rapports (succinct, rentrée, fin d'année) et les canevas
        classent vos classes d'après ce choix, quel que soit leur nom (« 10ème A », « Terminale SS 2 »…). La série reconnue
        d'après le nom est proposée : vérifiez-la, corrigez au besoin, puis enregistrez.
    </div>

    @if($classes->isEmpty())
        <div class="card theme-card"><div class="card-body text-center text-muted py-4">Aucune classe du secondaire général.</div></div>
    @else
        <form method="POST" action="{{ route('rapport-secondaire.series.store') }}">
            @csrf
            @if($retour)<input type="hidden" name="retour" value="{{ $retour }}">@endif
            <div class="card theme-card shadow-sm mb-3">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Classe</th><th style="min-width: 320px;">Série</th><th>État</th></tr></thead>
                            <tbody>
                                @foreach($classes as $c)
                                    @php $valeur = old('serie.'.$c->id_classe, $c->choisie ? $c->serie : $c->proposee); @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $c->nom_classe }}</td>
                                        <td>
                                            <select name="serie[{{ $c->id_classe }}]" class="form-select" required aria-label="Série de {{ $c->nom_classe }}">
                                                <option value="">— Choisir la série —</option>
                                                @foreach([10 => '10ème', 11 => '11ème', 12 => 'Terminales'] as $niveau => $libelleNiveau)
                                                    <optgroup label="{{ $libelleNiveau }}">
                                                        @foreach(array_filter(\App\Support\Series::RAPPORTS, fn ($s) => $s[1] === $niveau) as $code => [$libelle])
                                                            <option value="{{ $code }}" @selected($valeur === $code)>{{ $libelle }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            @if($c->choisie)
                                                <span class="badge bg-success">Choisie</span>
                                            @elseif($c->proposee)
                                                <span class="badge bg-warning text-dark">Proposée d'après le nom : à vérifier</span>
                                            @else
                                                <span class="badge bg-danger">Non reconnue : à choisir</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-end mb-4">
                @if($retour === 'succinct')
                    <a class="btn btn-light" href="{{ route('rapport-succinct.index', ['portee' => 'secondaire']) }}">Retour au rapport succinct</a>
                @elseif(in_array($retour, ['rentree', 'fin'], true))
                    <a class="btn btn-light" href="{{ route('rapport-secondaire.index', ['type' => $retour]) }}">Retour au rapport</a>
                @endif
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i>Enregistrer les séries</button>
            </div>
        </form>
    @endif
@endsection
