@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4 mt-2">
    <div class="breadcrumb-title pe-3">Tableau de Bord</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Espace enseignant</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card theme-card shadow-sm mb-4">
    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <p class="text-muted text-uppercase small fw-bold mb-1">Bienvenue</p>
            <h4 class="fw-bold mb-1">{{ $enseignant->nom_prenom_enseignant ?? auth()->user()->nomPrenom }}</h4>
            <div class="text-muted">{{ $enseignant->specialite ?: 'Enseignant' }}</div>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2">
                {{ session('nomEcole') ?: 'École' }}
            </span>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Mes classes</p>
                    <h3 class="fw-bold mb-0">{{ $totalClasses }}</h3>
                </div>
                <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-door-open fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Mes matières</p>
                    <h3 class="fw-bold mb-0">{{ $totalMatieres }}</h3>
                </div>
                <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-journal-bookmark fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Élèves concernés</p>
                    <h3 class="fw-bold mb-0">{{ $totalEleves }}</h3>
                </div>
                <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-people fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Heures émargées</p>
                    <h3 class="fw-bold mb-0">{{ number_format($heuresEmargees, 1, ',', ' ') }}</h3>
                </div>
                <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-clock-history fs-4"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Progression du cahier de présence</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Classe</th>
                                <th>Leçon</th>
                                <th>Date</th>
                                <th style="min-width: 240px;">Progression</th>
                                <th class="text-end">Durée</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teacherPresenceProgressRows as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row['classe'] }}</td>
                                    <td>{{ $row['titre'] }}</td>
                                    <td>{{ optional($row['date'])->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: {{ $row['percent'] }}%"></div>
                                            </div>
                                            <span class="fw-bold text-primary text-nowrap">{{ number_format($row['percent'], 0, ',', ' ') }}%</span>
                                        </div>
                                    </td>
                                    <td class="text-end">{{ number_format($row['hours'], 2, ',', ' ') }} h</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Aucune progression de présence validée pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card theme-card shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Mes affectations</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Matière</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td class="fw-bold">{{ $assignment->classe->nom_classe ?? 'N/A' }}</td>
                                    <td>{{ $assignment->matiere->nom_matiere ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">Aucune affectation enregistrée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($teacherPermissions['evaluations'])
            <div class="card theme-card shadow-sm">
                <div class="card-header bg-transparent border-0 p-4 pb-0">
                    <h5 class="fw-bold mb-0">Dernières évaluations</h5>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Évaluation</th>
                                    <th>Classe</th>
                                    <th>Matière</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentEvaluations as $ligne)
                                    <tr>
                                        <td class="fw-bold">{{ $ligne->evaluation->libeller ?? 'Évaluation' }}</td>
                                        <td>{{ $ligne->classe->nom_classe ?? 'N/A' }}</td>
                                        <td>{{ $ligne->matiere->nom_matiere ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">Aucune évaluation récente.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card theme-card shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Mes activités</h5>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Émargements</span>
                    <span class="fw-bold">{{ $totalEmargements }}</span>
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-muted">Présences</span>
                    <span class="fw-bold">{{ $totalPresences }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Évaluations</span>
                    <span class="fw-bold">{{ $evaluationsCount }}</span>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Accès rapides</h5>
                <div class="d-grid gap-3">
                    @if($teacherPermissions['emargement'])
                        <a href="{{ route('enseignants.emargements') }}" class="btn btn-outline-primary text-start p-3 rounded-3">
                            <i class="bi bi-pencil-square me-2"></i>Émargements
                        </a>
                    @endif
                    @if($teacherPermissions['presence'])
                        <a href="{{ route('enseignants.presences') }}" class="btn btn-outline-info text-start p-3 rounded-3">
                            <i class="bi bi-clipboard-check me-2"></i>Cahier de présence
                        </a>
                    @endif
                    @if($teacherPermissions['evaluations'])
                        <a href="{{ route('evaluations.index') }}" class="btn btn-outline-warning text-start p-3 rounded-3">
                            <i class="bi bi-journal-check me-2"></i>Évaluations
                        </a>
                    @endif
                    @if($teacherPermissions['programmes'])
                        <a href="{{ route('programmes.index') }}" class="btn btn-outline-success text-start p-3 rounded-3">
                            <i class="bi bi-file-earmark-text me-2"></i>Programmes officiels
                        </a>
                    @endif
                    @if($teacherPermissions['timetable'])
                        <a href="{{ route('pedagogie.timetable') }}" class="btn btn-outline-secondary text-start p-3 rounded-3">
                            <i class="bi bi-calendar-week me-2"></i>Mon emploi du temps
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if($teacherPermissions['emargement'])
            <div class="card theme-card shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Derniers émargements</h5>
                    @forelse($recentEmargements as $emargement)
                        <div class="border-bottom py-2">
                            <div class="fw-bold">{{ $emargement->matiere->nom_matiere ?? 'Matière' }}</div>
                            <div class="small text-muted">
                                {{ $emargement->classe->nom_classe ?? 'Classe' }} -
                                {{ optional($emargement->date_emargement)->format('d/m/Y') }}
                            </div>
                        </div>
                    @empty
                        <div class="text-muted">Aucun émargement récent.</div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
</style>
@endsection
