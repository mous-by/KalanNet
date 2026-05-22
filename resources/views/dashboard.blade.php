@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4 mt-2">
    <div class="breadcrumb-title pe-3">Tableau de Bord</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Aperçu</li>
            </ol>
        </nav>
    </div>
</div>


<!-- Stats Cards (KPIs) -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Total Élèves</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totalEleves) }}</h3>
                    <div class="d-flex gap-3 small mt-2">
                        <span class="text-info"><i class="bi bi-gender-male"></i> {{ $totalGarcons }}</span>
                        <span class="text-pink"><i class="bi bi-gender-female"></i> {{ $totalFilles }}</span>
                    </div>
                </div>
                <div class="widget-icon theme-icon-box rounded-3">
                    <i class="bi bi-people fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Enseignants</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totalEnseignants) }}</h3>
                    <small class="text-muted d-block mt-2">Total des effectifs</small>
                </div>
                <div class="widget-icon theme-icon-box rounded-3">
                    <i class="bi bi-person-badge fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Classes</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totalClasses) }}</h3>
                    <small class="text-muted d-block mt-2">Divisions pédagogiques</small>
                </div>
                <div class="widget-icon theme-icon-box rounded-3">
                    <i class="bi bi-door-open fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Taux d'Abandon</p>
                    <h3 class="fw-bold mb-0 text-danger">{{ $tauxAbandon }}%</h3>
                    <small class="text-muted d-block mt-2">{{ $totalAbandons }} élèves ont quitté</small>
                </div>
                <div class="widget-icon bg-danger-soft text-danger rounded-3 p-3">
                    <i class="bi bi-person-x fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-card">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Progression des enseignants par matière</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Enseignant</th>
                                <th>Classe</th>
                                <th>Matière</th>
                                <th style="min-width: 240px;">Progression</th>
                                <th class="text-center">Leçons</th>
                                <th class="text-end">Heures</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teacherProgressRows as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row['teacher'] }}</td>
                                    <td>{{ $row['classe'] }}</td>
                                    <td>{{ $row['matiere'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 8px;">
                                                <div class="progress-bar bg-success" style="width: {{ $row['percent'] }}%"></div>
                                            </div>
                                            <span class="fw-bold text-primary text-nowrap">{{ $row['percent'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="text-center">{{ $row['completed'] }}/{{ $row['total'] }}</td>
                                    <td class="text-end">{{ number_format($row['hours'], 1, ',', ' ') }} h</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Aucune progression disponible pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-card">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Progression du cahier de présence</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Enseignant</th>
                                <th>Classe</th>
                                <th>Leçon</th>
                                <th>Date</th>
                                <th style="min-width: 240px;">Progression</th>
                                <th class="text-end">Durée</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($presenceProgressRows as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row['teacher'] }}</td>
                                    <td>{{ $row['classe'] }}</td>
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
                                    <td colspan="6" class="text-center py-4 text-muted">Aucune progression de présence validée pour le moment.</td>
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
    <!-- Indicateurs Pédagogiques & Effectifs -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 bg-card mb-4">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Effectifs par Classe / Niveau</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Classe / Niveau</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Garçons</th>
                                <th class="text-center">Filles</th>
                                <th class="text-end">Taux</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classesData as $classe)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary">{{ $classe['nom'] }}</div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-primary-soft text-primary rounded-pill px-3">{{ $classe['total'] }}</span></td>
                                    <td class="text-center text-info"><i class="bi bi-gender-male"></i> {{ $classe['garcons'] }}</td>
                                    <td class="text-center text-pink"><i class="bi bi-gender-female"></i> {{ $classe['filles'] }}</td>
                                    <td class="text-end">
                                        <div class="progress" style="height: 6px; width: 60px; display: inline-block;">
                                            @php $percent = $totalEleves > 0 ? ($classe['total'] / $totalEleves) * 100 : 0; @endphp
                                            <div class="progress-bar bg-primary" style="width: {{ $percent }}%"></div>
                                        </div>
                                        <small class="ms-1 text-muted">{{ round($percent) }}%</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-card">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Inscriptions Récentes</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentEleves as $eleve)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }}</div>
                                        <small class="text-muted">{{ $eleve->matricule }}</small>
                                    </td>
                                    <td><span class="badge bg-light text-primary">{{ $eleve->classe->nom_classe ?? 'N/A' }}</span></td>
                                    <td><span class="badge bg-success-soft text-success px-2 py-1">Actif</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Aucune donnée récente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Side: Actions & Finances -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 bg-card mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Solde Caisse</h5>
                <div class="p-3 rounded-4 text-white mb-3" style="background-color: var(--theme-accent) !important;">
                    <p class="small mb-1 opacity-75">Disponible actuellement</p>
                    <h2 class="mb-0 fw-bold">{{ number_format($soldeCaisse, 0, ',', ' ') }} <small class="fs-6">FCFA</small></h2>
                </div>
                <div class="d-flex align-items-center justify-content-between small">
                    <span class="text-muted">Recettes Totales</span>
                    <span class="fw-bold text-success">{{ number_format($totalRecettes, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-card">
            <div class="card-body p-4">
                <h5 class="mb-4 fw-bold">Actions Rapides</h5>
                <div class="d-grid gap-3">
                    <a href="{{ route('inscriptions.create') }}" class="btn btn-outline-primary text-start p-3 rounded-3 d-flex align-items-center transition">
                        <i class="bi bi-person-plus fs-4 me-3"></i>
                        <div>
                            <div class="fw-bold">Inscrire un élève</div>
                            <small class="text-muted">Ajouter un apprenant</small>
                        </div>
                    </a>
                    <a href="{{ route('finances.paiements') }}" class="btn btn-outline-info text-start p-3 rounded-3 d-flex align-items-center transition">
                        <i class="bi bi-wallet2 fs-4 me-3"></i>
                        <div>
                            <div class="fw-bold">Nouveau Paiement</div>
                            <small class="text-muted">Enregistrer un versement</small>
                        </div>
                    </a>
                    <a href="{{ route('evaluations.index') }}" class="btn btn-outline-warning text-start p-3 rounded-3 d-flex align-items-center transition">
                        <i class="bi bi-journal-check fs-4 me-3"></i>
                        <div>
                            <div class="fw-bold">Évaluations</div>
                            <small class="text-muted">Saisir les notes</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
    .text-pink { color: #ec4899; }
    .bg-primary-soft { background-color: rgba(var(--bs-primary-rgb), 0.1); }
    .bg-success-soft { background-color: rgba(var(--bs-success-rgb), 0.1); }
    .bg-danger-soft { background-color: rgba(var(--bs-danger-rgb), 0.1); }
</style>
@endsection
