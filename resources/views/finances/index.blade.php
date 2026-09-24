@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('finances.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_management') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-0">
    <div class="col-12 col-md-3">
        @include('finances.partials.menu', ['active' => 'index'])
    </div>

    <div class="col-12 col-md-9 pt-4 pt-md-0 p-md-3">
    <div class="mb-3 d-flex justify-content-end gap-2">
        <button class="btn px-4 theme-pill-active">
            <i class="bi bi-file-earmark-bar-graph me-2"></i>{{ __('finances.report_button') }}
        </button>
        <a href="{{ route('finances.paiements') }}" class="btn px-4 theme-pill-active">
            <i class="bi bi-cash-stack me-2"></i>{{ __('finances.new_payment_button') }}
        </a>
    </div>


    <!-- Quick Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 border-start border-success border-4 shadow-sm bg-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold small mb-3">{{ __('finances.total_recettes') }}</h6>
                            <h2 class="fw-bold mb-0 text-success">{{ number_format($totalRecettes, \App\Support\Devise::decimales(), ',', ' ') }} <small class="fs-6">{{ \App\Support\Devise::symbole() }}</small></h2>
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
                            <h6 class="text-muted text-uppercase fw-bold small mb-3">{{ __('finances.total_depenses') }}</h6>
                            <h2 class="fw-bold mb-0 text-danger">{{ number_format($totalDepenses, \App\Support\Devise::decimales(), ',', ' ') }} <small class="fs-6">{{ \App\Support\Devise::symbole() }}</small></h2>
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
                            <h6 class="text-muted text-uppercase fw-bold small mb-3">{{ __('finances.solde_caisse') }}</h6>
                            <h2 class="fw-bold mb-0 text-primary">{{ number_format($caisse ? $caisse->montant_net : 0, \App\Support\Devise::decimales(), ',', ' ') }} <small class="fs-6">{{ \App\Support\Devise::symbole() }}</small></h2>
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
                    <h5 class="fw-bold mb-0">{{ __('finances.recent_payments_title') }}</h5>
                    <a href="{{ route('finances.paiements') }}" class="btn btn-sm btn-light">{{ __('finances.see_all') }}</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">{{ __('finances.th_eleve') }}</th>
                                <th class="py-3">{{ __('finances.label_classe') }}</th>
                                <th class="py-3">{{ __('finances.th_montant') }}</th>
                                <th class="py-3">{{ __('finances.th_date') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('finances.th_recu') }}</th>
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
                                    <td class="fw-bold">@devise($p->montant)</td>
                                    <td class="small">{{ date('d/m/Y', strtotime($p->date_paiement)) }}</td>
                                    <td class="px-4 text-end">
                                        <button class="btn btn-light btn-sm p-2"><i class="bi bi-printer"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center py-5">{{ __('finances.empty_recent_payments') }}</td></tr>
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
                    <h5 class="fw-bold mb-1">{{ __('finances.caisse_principale') }}</h5>
                    <p class="text-muted small mb-4">{{ __('finances.reference_label', ['reference' => $caisse ? $caisse->reference : 'N/A']) }}</p>

                    <div class="d-grid gap-3">
                        <div class="p-3 bg-light rounded-3 text-start d-flex justify-content-between align-items-center">
                            <span class="text-muted small fw-bold">{{ __('finances.statut_label') }}</span>
                            @if($caisse && $caisse->status == 1)
                                <span class="badge bg-success px-3">{{ __('finances.statut_ouverte') }}</span>
                            @else
                                <span class="badge bg-danger px-3">{{ __('finances.statut_fermee') }}</span>
                            @endif
                        </div>
                        <a href="{{ route('finances.caisse') }}" class="btn btn-outline-primary py-2">{{ __('finances.movement_history') }}</a>
                        <button class="btn btn-light py-2">{{ __('finances.caisse_closure') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
    </div>

    <style>
        .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
    </style>
@endsection
