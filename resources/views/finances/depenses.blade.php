@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('finances.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">{{ __('finances.breadcrumb_gestion') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_depenses') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('finances.paiements.partials.alerts')

    @if(!$caisse)
        <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-1">{{ __('finances.no_active_caisse_title') }}</h5>
            <p class="mb-0">{{ __('finances.no_active_caisse_desc') }}</p>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">{{ __('finances.caisse_label') }}</small>
                        <h5 class="fw-bold mb-0">{{ $caisse->reference }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">{{ __('finances.solde_actuel') }}</small>
                        <h4 class="fw-bold text-primary mb-0">@devise($caisse->montant_net)</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">{{ __('finances.en_attente_label') }}</small>
                        <h4 class="fw-bold text-warning mb-0">{{ $depenses->where('valide', false)->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm overflow-hidden">
            <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2 p-4 border-0">
                <h5 class="fw-bold mb-0">{{ __('finances.depenses_list_title') }}</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($caisse && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('decaissements_creation')))
                        <button class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white"
                                style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                data-bs-toggle="modal" data-bs-target="#decaissementModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>{{ __('finances.nouvelle_depense') }}</span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">{{ __('finances.th_date') }}</th>
                                <th class="py-3">{{ __('finances.th_motif') }}</th>
                                <th class="py-3">{{ __('finances.th_demande_par') }}</th>
                                <th class="py-3 text-end">{{ __('finances.th_montant') }}</th>
                                <th class="py-3">{{ __('finances.th_statut') }}</th>
                                <th class="py-3">{{ __('finances.th_valide_par') }}</th>
                                <th class="px-4 py-3 text-end">{{ __('classes.th_action') }}</th>
                            </tr>
                        </thead>
                    <tbody>
                        @forelse($depenses as $depense)
                            <tr id="decaissement-{{ $depense->id_decaissement }}">
                                <td class="px-4 py-3">{{ optional($depense->date_decaissement)->format('d/m/Y') }}</td>
                                <td class="fw-semibold py-3">{{ $depense->motif_decaissement }}</td>
                                <td>{{ $depense->utilisateur?->nomPrenom ?? __('finances.utilisateur_generic') }}</td>
                                <td class="text-end fw-bold {{ $depense->valide ? 'text-danger' : 'text-warning' }}">
                                    {{ $depense->valide ? '-' : '' }} @devise($depense->montant_decaissement)
                                </td>
                                <td>
                                    <span class="badge bg-{{ $depense->valide ? 'success' : 'warning' }} rounded-pill px-3">
                                        {{ $depense->valide ? __('finances.statut_validee') : __('finances.statut_en_attente') }}
                                    </span>
                                </td>
                                <td>
                                    @if($depense->valide)
                                        {{ $depense->validateur?->nomPrenom ?? __('finances.validation_directe') }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end px-4">
                                    @if(!$depense->valide && (auth()->user()->droit === 'SupAdmin' || auth()->user()->droit === 'Admin' || auth()->user()->userHasPermission('decaissements_validation')))
                                        <form method="POST" action="{{ route('finances.decaissements.validate', $depense->id_decaissement) }}" class="d-inline js-validate-decaissement">
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
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">{{ __('finances.empty_depenses') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="decaissementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content card theme-card">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('finances.nouvelle_depense') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                    </div>
                    <form method="POST" action="{{ route('finances.decaissements.store') }}">
                        @csrf
                        <input type="hidden" name="id_caisse" value="{{ $caisse->id_caisse }}">
                        <div class="modal-body">
                            <div class="alert alert-info border-0 border-start border-info border-4">
                                {{ __('finances.decaissement_pending_info') }}
                            </div>
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
                            <button type="submit" class="btn btn-primary">{{ __('finances.submit_button') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        @php
            $depensesI18n = [
                'confirmTitle' => __('finances.confirm_validate_depense_title'),
                'confirmText' => __('finances.confirm_validate_depense_text'),
                'confirmYes' => __('finances.confirm_yes_validate'),
                'cancel' => __('finances.cancel_button'),
            ];
        @endphp
        <script>
            const depensesI18n = @json($depensesI18n);
            document.querySelectorAll('.js-validate-decaissement').forEach((form) => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    Swal.fire({
                        title: depensesI18n.confirmTitle,
                        text: depensesI18n.confirmText,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: depensesI18n.confirmYes,
                        cancelButtonText: depensesI18n.cancel,
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
