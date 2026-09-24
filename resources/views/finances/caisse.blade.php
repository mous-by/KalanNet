@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('finances.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">{{ __('finances.breadcrumb_gestion') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_journal_caisse') }}</li>
                </ol>
            </nav>
        </div>
        @if($caisse)
            <div class="ms-auto d-flex gap-2">
                @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('decaissements_creation'))
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#decaissementModal"><i class="bi bi-dash-lg me-1"></i>{{ __('finances.sortie_caisse') }}</button>
                @endif
                @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('encaissement_creation'))
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#encaissementModal"><i class="bi bi-plus-lg me-1"></i>{{ __('finances.entree_caisse') }}</button>
                @endif
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    @if($caisse)
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase fw-bold small mb-3">{{ __('finances.solde_initial') }}</h6>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($caisse->montant_initial, \App\Support\Devise::decimales(), ',', ' ') }} <small class="fs-6">{{ \App\Support\Devise::symbole() }}</small></h4>
                        </div>
                        <div class="widget-icon theme-icon-box rounded-3">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-primary text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold small mb-3">{{ __('finances.solde_actuel_caisse') }}</h6>
                            <h2 class="fw-bold mb-0">{{ number_format($caisse->montant_net, \App\Support\Devise::decimales(), ',', ' ') }} <small class="fs-6">{{ \App\Support\Devise::symbole() }}</small></h2>
                        </div>
                        <div class="widget-icon theme-icon-soft rounded-3">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <div class="d-inline-block caisse-reference-box p-3 rounded-4 shadow-sm">
                <small class="d-block text-uppercase fw-bold">{{ __('finances.reference_caisse') }}</small>
                <span class="fs-4 fw-bold font-monospace">{{ $caisse->reference }}</span>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm overflow-hidden">
        <div class="card-header theme-header p-4 border-0">
            <h5 class="fw-bold mb-0">{{ __('finances.journal_mouvements_title') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3 border-0 small fw-bold text-muted text-uppercase">{{ __('finances.th_date') }}</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">{{ __('finances.th_type') }}</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">{{ __('finances.th_motif_libelle') }}</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase text-end">{{ __('finances.th_montant') }}</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">{{ __('finances.th_statut') }}</th>
                        <th class="px-4 py-3 border-0 small fw-bold text-muted text-uppercase text-end">{{ __('classes.th_action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mouvements as $m)
                        <tr id="{{ $m->type == 'DEPENSE' ? 'decaissement-' . $m->id_decaissement : '' }}" class="{{ $m->type == 'DEPENSE' ? 'table-danger-light' : '' }}">
                            <td class="px-4 py-3 small">{{ date('d/m/Y H:i', strtotime($m->date)) }}</td>
                            <td>
                                @if($m->type == 'RECETTE')
                                    <span class="badge bg-success-soft text-success rounded-pill px-3"><i class="bi bi-arrow-down-left me-1"></i> {{ __('finances.entree_label') }}</span>
                                @else
                                    <span class="badge bg-danger-soft text-danger rounded-pill px-3"><i class="bi bi-arrow-up-right me-1"></i> {{ __('finances.sortie_label') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">{{ $m->motif }}</div>
                                @if($m->type == 'RECETTE')
                                    <small class="text-muted">{{ $m->type_operation }}</small>
                                @else
                                    <small class="text-muted">{{ __('finances.demande_par', ['nom' => $m->utilisateur?->nomPrenom ?? __('finances.utilisateur_generic')]) }}</small>
                                @endif
                            </td>
                            <td class="text-end fw-bold {{ $m->type == 'DEPENSE' ? ($m->valide ? 'text-danger' : 'text-warning') : 'text-success' }}">
                                @if($m->type == 'DEPENSE')
                                    {{ $m->valide ? '-' : '' }} @devise($m->montant)
                                @else
                                    + @devise($m->montant)
                                @endif
                            </td>
                            <td>
                                @if($m->type == 'DEPENSE')
                                    <span class="badge bg-{{ $m->valide ? 'success' : 'warning' }} rounded-pill px-3">
                                        {{ $m->valide ? __('finances.statut_validee') : __('finances.statut_en_attente') }}
                                    </span>
                                @else
                                    <span class="badge bg-success rounded-pill px-3">{{ __('finances.statut_validee') }}</span>
                                @endif
                            </td>
                            <td class="px-4 text-end">
                                @if($m->type == 'DEPENSE' && !$m->valide && (auth()->user()->droit === 'SupAdmin' || auth()->user()->droit === 'Admin' || auth()->user()->userHasPermission('decaissements_validation')))
                                    <form method="POST" action="{{ route('finances.decaissements.validate', $m->id_decaissement) }}" class="d-inline js-validate-decaissement">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check2-circle me-1"></i>{{ __('finances.validate_button') }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">{{ __('finances.empty_mouvements') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4 text-center">
        <i class="bi bi-exclamation-triangle fs-1 mb-3 d-block"></i>
        <h4 class="fw-bold">{{ __('finances.no_active_caisse_title2') }}</h4>
        <p class="mb-0">{{ __('finances.no_active_caisse_config_desc') }}</p>
        <button class="btn btn-primary mt-3 px-4" data-bs-toggle="modal" data-bs-target="#caisseModal" style="background-color: var(--theme-accent) !important; border-color: var(--theme-accent) !important; color: white !important;">
            <i class="bi bi-plus-lg me-2"></i>{{ __('finances.creer_caisse') }}
        </button>
    </div>
    @endif

    <div class="modal fade" id="caisseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('finances.caisse_registration_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                </div>
                <form method="POST" action="{{ route('finances.caisse.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('finances.label_libelle_caisse') }} <span class="text-danger">*</span></label>
                            <input type="text" name="libelle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('finances.label_montant_initial') }} <span class="text-danger">*</span></label>
                            <input type="number" name="montant_initial" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('finances.label_statut') }}</label>
                            <select name="status" class="form-select">
                                <option value="1">{{ __('finances.statut_active') }}</option>
                                <option value="0">{{ __('finances.statut_inactive') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('finances.cancel_button') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('finances.save_button') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($caisse)
        <div class="modal fade" id="encaissementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content card theme-card">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('finances.nouvelle_entree_caisse') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                    </div>
                    <form method="POST" action="{{ route('finances.encaissements.store') }}">
                        @csrf
                        <input type="hidden" name="id_caisse" value="{{ $caisse->id_caisse }}">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('finances.caisse_reference_label') }}</label>
                                    <input type="text" class="form-control" value="{{ $caisse->reference }} ({{ \App\Support\Devise::format($caisse->montant_net) }})" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('eleves.label_annee') }} <span class="text-danger">*</span></label>
                                    <select name="id_annee_scolaire" class="form-select" required>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('finances.label_type_operation') }} <span class="text-danger">*</span></label>
                                    <select name="type_operation" class="form-select" required>
                                        <option value="encaissement divers">{{ __('finances.encaissement_divers') }}</option>
                                        <option value="encaissement scolaire">{{ __('finances.encaissement_scolaire') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('finances.label_date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="date_encaissement" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('finances.label_motif') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="motif_encaissement" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('finances.label_montant') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="montant_encaissement" class="form-control" min="1" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('finances.cancel_button') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('finances.submit_button') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="decaissementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content card theme-card">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('finances.nouvelle_sortie_caisse') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                    </div>
                    <form method="POST" action="{{ route('finances.decaissements.store') }}">
                        @csrf
                        <input type="hidden" name="id_caisse" value="{{ $caisse->id_caisse }}">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('finances.caisse_reference_label') }}</label>
                                    <input type="text" class="form-control" value="{{ $caisse->reference }} ({{ \App\Support\Devise::format($caisse->montant_net) }})" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('eleves.label_annee') }} <span class="text-danger">*</span></label>
                                    <select name="id_annee_scolaire" class="form-select" required>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ __('finances.label_date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="date_decaissement" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('finances.label_motif') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="motif_decaissement" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('finances.label_montant') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="montant_decaissement" class="form-control" min="1" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('finances.cancel_button') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('finances.send_button') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

<style>
    .table-danger-light {
        background-color: rgba(220, 53, 69, 0.02);
    }
    .widget-icon {
        width: 54px;
        height: 54px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .caisse-reference-box {
        background: #ffffff !important;
        border: 1px solid #d8e2ee;
    }
    .caisse-reference-box small {
        color: #475569 !important;
        letter-spacing: 0;
    }
    .caisse-reference-box span {
        color: #0f172a !important;
    }
</style>
@push('scripts')
    @php
        $caisseI18n = [
            'confirmTitle' => __('finances.confirm_validate_depense_title'),
            'confirmText' => __('finances.confirm_validate_depense_text'),
            'confirmYes' => __('finances.confirm_yes_validate'),
            'cancel' => __('finances.cancel_button'),
        ];
    @endphp
    <script>
        const caisseI18n = @json($caisseI18n);
        document.querySelectorAll('.js-validate-decaissement').forEach((form) => {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                Swal.fire({
                    title: caisseI18n.confirmTitle,
                    text: caisseI18n.confirmText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: caisseI18n.confirmYes,
                    cancelButtonText: caisseI18n.cancel,
                    confirmButtonColor: '#198754'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
@endsection
