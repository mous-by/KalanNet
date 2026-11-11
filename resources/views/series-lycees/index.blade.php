@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Classes & cours</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Séries des lycées (tous les établissements)</li>
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
        Série de chaque classe du secondaire général, pour tous les établissements. Les rapports (succinct, rentrée, fin d'année)
        et les canevas classent les classes d'après elle, quel que soit leur nom. Appliquez d'abord les séries reconnues d'après
        le nom, puis choisissez à la main celles qui ne l'ont pas été.
    </div>

    <div class="row g-3 mb-3">
        @foreach([['Classes du général', $stats['total'], 'primary'], ['Séries choisies', $stats['choisies'], 'success'], ['Reconnues, à confirmer', $stats['proposees'], 'warning'], ['Non reconnues', $stats['inconnues'], 'danger']] as [$libelle, $nombre, $couleur])
            <div class="col-6 col-lg-3">
                <div class="card theme-card shadow-sm h-100 border-start border-4 border-{{ $couleur }}"><div class="card-body py-3">
                    <div class="small text-muted text-uppercase fw-bold">{{ $libelle }}</div>
                    <div class="fs-4 fw-bold">{{ $nombre }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card theme-card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <div class="me-auto">
                <div class="fw-bold">Appliquer toutes les séries reconnues ({{ $stats['proposees'] }} classe(s), {{ $stats['ecoles'] }} établissement(s) concernés)</div>
                <div class="small text-muted">Chaque classe non vérifiée reçoit la série reconnue d'après son nom. Les établissements peuvent ensuite corriger depuis leurs rapports.</div>
            </div>
            <form method="POST" action="{{ route('series-lycees.propositions') }}" onsubmit="return confirm('Appliquer la série reconnue à {{ $stats['proposees'] }} classe(s) ?');">
                @csrf
                <button type="submit" class="btn btn-primary" @disabled($stats['proposees'] === 0)><i class="bi bi-magic me-1"></i>Appliquer les séries reconnues</button>
            </form>
        </div>
    </div>

    <form method="GET" action="{{ route('series-lycees.index') }}" class="row g-2 align-items-end mb-3">
        <div class="col-sm-5">
            <label class="form-label small fw-bold" for="q">Établissement</label>
            <input type="search" name="q" id="q" class="form-control" value="{{ $recherche }}" placeholder="Rechercher un établissement">
        </div>
        <div class="col-sm-4">
            <label class="form-label small fw-bold" for="filtre">Classes affichées</label>
            <select name="filtre" id="filtre" class="form-select" onchange="this.form.submit()">
                <option value="a_verifier" @selected($filtre !== 'toutes')>À vérifier (série non choisie)</option>
                <option value="toutes" @selected($filtre === 'toutes')>Toutes</option>
            </select>
        </div>
        <div class="col-sm-3"><button type="submit" class="btn btn-light w-100"><i class="bi bi-search me-1"></i>Filtrer</button></div>
    </form>

    @if($parEcole->isEmpty())
        <div class="card theme-card"><div class="card-body text-center text-muted py-4">
            {{ $filtre === 'toutes' ? 'Aucune classe du secondaire général.' : 'Toutes les classes du secondaire général ont leur série.' }}
        </div></div>
    @else
        <form method="POST" action="{{ route('series-lycees.enregistrer') }}" id="form-series">
            @csrf
            <input type="hidden" name="series_json" id="series_json">
            <div class="d-flex justify-content-end mb-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-check2 me-1"></i>Enregistrer les séries choisies</button>
            </div>
            @foreach($parEcole as $ecole => $classes)
                <div class="card theme-card shadow-sm mb-3">
                    <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ $ecole }} <span class="fw-normal small">({{ $classes->count() }} classe(s))</span></h6></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Classe</th><th style="min-width: 320px;">Série</th><th>État</th></tr></thead>
                                <tbody>
                                    @foreach($classes as $c)
                                        @php $valeur = $c->choisie ? $c->serie : $c->proposee; @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $c->nom_classe }}</td>
                                            <td>
                                                <select name="serie[{{ $c->id_classe }}]" class="form-select form-select-sm" aria-label="Série de {{ $c->nom_classe }} ({{ $ecole }})">
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
                                                    <span class="badge bg-warning text-dark">Reconnue d'après le nom : à confirmer</span>
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
            @endforeach
            <div class="d-flex justify-content-end mb-4">
                <button type="submit" class="btn btn-success px-4"><i class="bi bi-check2 me-1"></i>Enregistrer les séries choisies</button>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
    <script>
        // Toutes les séries partent dans un seul champ : la limite de champs de PHP ne peut rien tronquer.
        document.getElementById('form-series')?.addEventListener('submit', (e) => {
            const series = {};
            e.target.querySelectorAll('select[name^="serie["]').forEach((s) => {
                series[s.name.slice(6, -1)] = s.value;
                s.removeAttribute('name');
            });
            document.getElementById('series_json').value = JSON.stringify(series);
        });
    </script>
@endpush
