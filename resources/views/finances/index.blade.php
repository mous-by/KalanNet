@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Finances</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Gestion Financière</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="mb-3 d-flex justify-content-end gap-2">
        <button class="btn px-4 theme-pill-active">
            <i class="bi bi-file-earmark-bar-graph me-2"></i>Rapport
        </button>
        <a href="{{ route('finances.paiements') }}" class="btn px-4 theme-pill-active">
            <i class="bi bi-cash-stack me-2"></i>Nouveau Paiement
        </a>
    </div>


    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 border-start border-success border-4 shadow-sm bg-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold small mb-3">Total Recettes</h6>
                            <h2 class="fw-bold mb-0 text-success">{{ number_format($totalRecettes, 0, ',', ' ') }} <small class="fs-6">FCFA</small></h2>
                        </div>
                        <div class="widget-icon theme-icon-box rounded-3">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 border-start border-danger border-4 shadow-sm bg-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold small mb-3">Total Dépenses</h6>
                            <h2 class="fw-bold mb-0 text-danger">{{ number_format($totalDepenses, 0, ',', ' ') }} <small class="fs-6">FCFA</small></h2>
                        </div>
                        <div class="widget-icon theme-icon-box rounded-3">
                            <i class="bi bi-graph-down-arrow fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 border-start border-primary border-4 shadow-sm bg-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold small mb-3">Solde en Caisse</h6>
                            <h2 class="fw-bold mb-0 text-primary">{{ number_format($caisse ? $caisse->montant_net : 0, 0, ',', ' ') }} <small class="fs-6">FCFA</small></h2>
                        </div>
                        <div class="widget-icon theme-icon-box rounded-3">
                            <i class="bi bi-safe2 fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Payments -->
        <div class="col-lg-8">
            <div class="card theme-card shadow-sm overflow-hidden h-100">
                <div class="card-header theme-header p-4 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Derniers Paiements Élèves</h5>
                    <a href="{{ route('finances.paiements') }}" class="btn btn-sm btn-light">Tout voir</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Élève</th>
                                <th class="py-3">Classe</th>
                                <th class="py-3">Montant</th>
                                <th class="py-3">Date</th>
                                <th class="px-4 py-3 text-end">Reçu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPaiements as $p)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="fw-bold">{{ $p->eleve->nom_eleve }} {{ $p->eleve->prenom_eleve }}</div>
                                        <small class="text-muted">{{ $p->motif }}</small>
                                    </td>
                                    <td><span class="badge bg-light text-primary border border-primary-subtle">{{ $p->classe->nom_classe }}</span></td>
                                    <td class="fw-bold">{{ number_format($p->montant, 0, ',', ' ') }}</td>
                                    <td class="small">{{ date('d/m/Y', strtotime($p->date_paiement)) }}</td>
                                    <td class="px-4 text-end">
                                        <button class="btn btn-light btn-sm p-2"><i class="bi bi-printer"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5">Aucun paiement récent.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cash State Card -->
        <div class="col-lg-4">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-4 text-center">
                    <div class="icon-box theme-icon-soft rounded-circle mx-auto mb-4 p-4" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-safe fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Caisse Principale</h5>
                    <p class="text-muted small mb-4">Référence: {{ $caisse ? $caisse->reference : 'N/A' }}</p>
                    
                    <div class="d-grid gap-3">
                        <div class="p-3 bg-light rounded-3 text-start d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-bold">STATUT</span>
                            @if($caisse && $caisse->status == 1)
                                <span class="badge bg-success px-3">Ouverte</span>
                            @else
                                <span class="badge bg-danger px-3">Fermée</span>
                            @endif
                        </div>
                        <a href="{{ route('finances.caisse') }}" class="btn btn-outline-primary py-2">Historique des Mouvements</a>
                        <button class="btn btn-light py-2">Clôture de Caisse</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
    </style>
@endsection
