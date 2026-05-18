@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    @if(auth()->user()->droit === 'SupAdmin')
                        <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">Années scolaires</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('configuration.partials.flash')

    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>
        <div class="col-12 col-lg-9">
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-calendar3 me-2"></i>Liste des années scolaires</h5>
                    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('annees_scolaires_apercu'))
                        <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white" 
                                style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                data-bs-toggle="modal" data-bs-target="#addNewAnneeModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>Ajouter</span>
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.annees') }}" method="GET" class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher une année..." value="{{ request('search') }}">
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Année</th>
                                    <th>Date début</th>
                                    <th>Date fin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($annees as $annee)
                                    @php($isCurrent = $anneeEnCours && (int) $anneeEnCours->id_anneeScolaire === (int) $annee->id_anneeScolaire)
                                    <tr @class(['table-success fw-semibold' => $isCurrent])>
                                        <td class="fw-bold">
                                            {{ $annee->annee }}
                                            @if($isCurrent)
                                                <span class="badge bg-success ms-2">En cours</span>
                                            @endif
                                        </td>
                                        <td>{{ $annee->date_debut ?? 'N/A' }}</td>
                                        <td>{{ $annee->date_fin ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Aucune année scolaire en cours trouvée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($annees->hasPages())
                        <div class="mt-4">{{ $annees->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Année Scolaire -->
    <div class="modal fade" id="addNewAnneeModal" tabindex="-1" aria-labelledby="addNewAnneeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--theme-accent) !important;">
                    <h5 class="modal-title fw-bold" id="addNewAnneeModalLabel">
                        <i class="bi bi-calendar-plus me-2"></i>Nouvelle Année Scolaire
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('configuration.annees.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="annee" class="form-label fw-semibold">Année Scolaire</label>
                            <input type="text" class="form-control" id="annee" name="annee" required placeholder="Ex: 2025-2026">
                            <small class="text-muted">Format recommandé : AAAA-AAAA</small>
                        </div>
                        <div class="mb-3">
                            <label for="date_debut" class="form-label fw-semibold">Date de début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_fin" class="form-label fw-semibold">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn text-white px-4 fw-semibold" style="background-color: var(--theme-accent) !important;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
