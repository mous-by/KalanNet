@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Classes Officielles</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Référentiel des classes officielles</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-body p-4">
                <p class="mb-2 fw-bold text-muted">Filtrer par</p>
                <form action="{{ route('configuration.classes-officielles') }}" method="GET" class="row g-3" data-auto-filter="true">
                    <div class="col-md-12">
                        <label class="form-label" for="search">Recherche rapide</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" id="search" class="form-control border-start-0 rounded-end-3" placeholder="Nom ou ordre d'enseignement..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary px-4 ms-2 rounded-3">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div class="ms-auto">
                    @if(auth()->user()->droit === 'SupAdmin')
                        <a href="{{ route('classes.associations') }}" class="btn btn-primary px-4">
                            <i class="bi bi-link-45deg me-2"></i>Associer classes
                        </a>
                    @endif
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-3">
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-2 theme-pill-active">
                            <i class="bi bi-building-check me-2"></i>Liste classes officielles
                        </button>
                    </li>
                </ul>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Classe officielle</th>
                            <th>Ordre d'enseignement</th>
                            <th>Classes associées</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classesOfficielles as $index => $classeOfficielle)
                            <tr>
                                <td>{{ $classesOfficielles->firstItem() + $index }}</td>
                                <td class="fw-bold">{{ $classeOfficielle->nom_classe_officielle }}</td>
                                <td>{{ $ordres[$classeOfficielle->ordre_enseignement] ?? $classeOfficielle->ordre_enseignement }}</td>
                                <td><span class="badge bg-light text-primary border border-primary-subtle rounded-pill">{{ $classeOfficielle->classes_count }} classe(s)</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Aucune classe officielle trouvée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($classesOfficielles->hasPages())
                <div class="mt-4">{{ $classesOfficielles->links() }}</div>
            @endif
        </div>
    </div>
@endsection
