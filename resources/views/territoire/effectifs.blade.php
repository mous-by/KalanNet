@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ Auth::user()->droit === 'DAE' ? 'Espace DAE' : 'Espace DCAP' }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Effectifs</li>
            </ol>
        </nav>
    </div>
</div>

@php
    $totEleves = $effectifs->sum('eleves');
    $totFilles = $effectifs->sum('filles');
    $totEnseignants = $effectifs->sum('enseignants');
    $nombre = fn ($n) => number_format($n, 0, ',', ' ');
@endphp

<div class="card theme-card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('territoire.effectifs') }}" class="row g-2 align-items-end">
            @if($caps->isNotEmpty())
                <div class="col-sm-5">
                    <label class="form-label small fw-semibold" for="cap">CAP</label>
                    <select id="cap" name="cap" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les CAP de l'académie</option>
                        @foreach($caps as $cap)
                            <option value="{{ $cap->id_cap }}" @selected((int) $capChoisi === (int) $cap->id_cap)>{{ $cap->nom_cap }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-sm-5">
                <label class="form-label small fw-semibold" for="recherche">École</label>
                <input type="search" id="recherche" name="recherche" class="form-control" value="{{ $recherche }}" placeholder="Rechercher une école">
            </div>
            <div class="col-sm-2 d-grid">
                <button type="submit" class="btn btn-light">Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    @include('dashboards.partials.chiffre', ['icone' => 'bi-building', 'libelle' => 'Écoles', 'valeur' => $effectifs->count()])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-people', 'libelle' => 'Élèves', 'valeur' => $totEleves, 'detail' => $nombre($totFilles) . ' filles · ' . $nombre($totEleves - $totFilles) . ' garçons'])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-grid-3x3-gap', 'libelle' => 'Classes', 'valeur' => $effectifs->sum(fn ($e) => $e->classes->count())])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-person-badge', 'libelle' => 'Enseignants', 'valeur' => $totEnseignants, 'detail' => $nombre($effectifs->sum('enseignantes')) . ' femmes · ' . $nombre($totEnseignants - $effectifs->sum('enseignantes')) . ' hommes'])
</div>

<p class="small text-muted">Effectifs en lecture seule : élèves inscrits et actifs, enseignants en poste. Cliquez sur une école pour voir le détail par classe.</p>

<div class="accordion" id="effectifsEcoles">
    @forelse($effectifs as $ecole)
        <div class="accordion-item">
            <h2 class="accordion-header" id="tete{{ $ecole->id }}">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#corps{{ $ecole->id }}" aria-expanded="false" aria-controls="corps{{ $ecole->id }}">
                    <div class="d-flex flex-wrap justify-content-between w-100 me-3 gap-2">
                        <div>
                            <span class="fw-semibold">{{ $ecole->nom }}</span>
                            <span class="badge {{ $ecole->publique ? 'bg-info text-dark' : 'bg-light text-dark border' }} ms-1">{{ $ecole->publique ? 'Publique' : 'Privée' }}</span>
                            @if($ecole->cap && $caps->isNotEmpty())<div class="small text-muted">{{ $ecole->cap }}</div>@endif
                        </div>
                        <div class="small text-nowrap" style="font-variant-numeric: tabular-nums;">
                            <strong>{{ $nombre($ecole->eleves) }}</strong> élèves · <strong>{{ $ecole->classes->count() }}</strong> classes · <strong>{{ $ecole->enseignants }}</strong> enseignants
                        </div>
                    </div>
                </button>
            </h2>
            <div id="corps{{ $ecole->id }}" class="accordion-collapse collapse" aria-labelledby="tete{{ $ecole->id }}" data-bs-parent="#effectifsEcoles">
                <div class="accordion-body">
                    <p class="small mb-2">Enseignants : <strong>{{ $ecole->enseignants }}</strong> ({{ $ecole->enseignantes }} femmes, {{ $ecole->enseignants - $ecole->enseignantes }} hommes)</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Classe</th><th>Niveau</th><th class="text-end">Filles</th><th class="text-end">Garçons</th><th class="text-end">Total</th></tr>
                            </thead>
                            <tbody style="font-variant-numeric: tabular-nums;">
                                @forelse($ecole->classes as $classe)
                                    <tr>
                                        <td class="fw-semibold">{{ $classe->nom }}</td>
                                        <td class="small">{{ $classe->niveau }}</td>
                                        <td class="text-end">{{ $classe->filles }}</td>
                                        <td class="text-end">{{ $classe->garcons }}</td>
                                        <td class="text-end fw-semibold">{{ $classe->total }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-muted text-center py-2">Aucune classe enregistrée.</td></tr>
                                @endforelse
                            </tbody>
                            @if($ecole->classes->isNotEmpty())
                                <tfoot class="table-light" style="font-variant-numeric: tabular-nums;">
                                    <tr><th colspan="2">Total de l'école</th><th class="text-end">{{ $ecole->filles }}</th><th class="text-end">{{ $ecole->garcons }}</th><th class="text-end">{{ $ecole->eleves }}</th></tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card"><div class="card-body text-muted text-center">Aucune école trouvée.</div></div>
    @endforelse
</div>
@endsection
