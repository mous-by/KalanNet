@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between bg-card p-4 rounded-4 shadow-sm">
                <div class="d-flex align-items-center">
                    <a href="{{ route('classes.index') }}" class="btn btn-light rounded-circle p-2 me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h2 class="mb-1 fw-bold">{{ $classe->nom_classe }}</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
                                <li class="breadcrumb-item active">{{ $classe->nom_classe }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary rounded-pill px-4"><i class="bi bi-printer me-2"></i>Liste de classe</button>
                    <button class="btn btn-primary rounded-pill px-4"><i class="bi bi-pencil me-2"></i>Modifier la Classe</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Stats Row -->
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-primary text-white overflow-hidden position-relative">
                <div class="card-body p-4 position-relative z-1">
                    <h6 class="text-white-50 text-uppercase fw-bold small mb-4">Effectif Total</h6>
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="display-5 fw-bold mb-0">{{ $classe->eleves()->count() }}</h2>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                    <p class="mt-4 mb-0 text-white-50">Élèves inscrits pour l'année en cours</p>
                </div>
                <div class="position-absolute bottom-0 end-0 opacity-10 p-4">
                    <i class="bi bi-building" style="font-size: 8rem;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted text-uppercase fw-bold small mb-4">Volume Horaire / Semaine</h6>
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="display-5 fw-bold mb-0">--</h2>
                        <div class="widget-icon bg-info text-white rounded-3">
                            <i class="bi bi-clock fs-4"></i>
                        </div>
                    </div>
                    <p class="mt-4 mb-0 text-muted">Heures de cours cumulées</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100">
                <div class="card-body p-4">
                    <h6 class="text-muted text-uppercase fw-bold small mb-4">Nombre de Matières</h6>
                    <div class="d-flex align-items-center justify-content-between">
                        <h2 class="display-5 fw-bold mb-0">{{ $classe->ligneClasses()->count() }}</h2>
                        <div class="widget-icon bg-warning text-white rounded-3">
                            <i class="bi bi-journal-text fs-4"></i>
                        </div>
                    </div>
                    <p class="mt-4 mb-0 text-muted">Disciplines enseignées</p>
                </div>
            </div>
        </div>

        <!-- Subjects and Teachers Table -->
        <div class="col-12">
            <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
                <div class="card-header bg-white p-4 border-0">
                    <h5 class="fw-bold mb-0">Répartition Pédagogique</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 border-0 small fw-bold text-muted text-uppercase">Matière</th>
                                <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Coefficient</th>
                                <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Enseignant</th>
                                <th class="px-4 py-3 border-0 text-end small fw-bold text-muted text-uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classe->ligneClasses as $lc)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="icon-box-sm bg-info-soft text-info rounded-3 p-2 me-3">
                                                <i class="bi bi-journal-check"></i>
                                            </div>
                                            <span class="fw-bold">{{ $lc->matiere->nom_matiere ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark">{{ number_format($lc->coefficient, 2) }}</span></td>
                                    <td>
                                        @if($lc->enseignant)
                                            <div class="d-flex align-items-center">
                                                <img src="{{ asset($lc->enseignant->avatar_enseignant ?: 'images/default_avatar.png') }}" alt="" class="rounded-circle me-2" width="30" height="30">
                                                <span>{{ $lc->enseignant->nom_prenom_enseignant }}</span>
                                            </div>
                                        @else
                                            <span class="text-danger small italic"><i class="bi bi-exclamation-triangle me-1"></i> Non assigné</span>
                                        @endif
                                    </td>
                                    <td class="px-4 text-end">
                                        <button class="btn btn-light btn-sm rounded-circle p-2"><i class="bi bi-pencil"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="text-muted">Aucune matière assignée à cette classe.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
</style>
@endsection
