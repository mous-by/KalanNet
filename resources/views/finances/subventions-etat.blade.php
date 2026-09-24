@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('finances.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">{{ __('finances.breadcrumb_gestion') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_subventions') }}</li>
                </ol>
            </nav>
        </div>
        @if($caisse && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasAnyPermission(['subventions_etat_encaisser', 'paiements_faire'])))
            <div class="ms-auto">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#subventionModal">
                    <i class="bi bi-bank me-1"></i>{{ __('finances.encaisser_subvention') }}
                </button>
            </div>
        @endif
    </div>

    @include('finances.paiements.partials.alerts')

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('finances.subventions-etat') }}" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('finances.label_annee_concernee') }}</label>
                    <select name="annee_scolaire_id" class="form-select" onchange="this.form.submit()" required>
                        <option value="">{{ __('finances.choose_ellipsis') }}</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['annee_scolaire_id'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('finances.label_classe') }}</label>
                    <select name="classe_id" class="form-select" onchange="this.form.submit()">
                        <option value="">{{ __('finances.all_classes_label') }}</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" @selected(($filters['classe_id'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if(!$caisse)
        <div class="alert alert-warning border-0 border-start border-warning border-4">{{ __('finances.no_active_caisse_subvention') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">{{ __('finances.echeances_ouvertes') }}</small>
                    <h4 class="fw-bold mb-0">{{ $subventionRows->count() }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">{{ __('finances.montant_attendu_etat') }}</small>
                    <h4 class="fw-bold text-warning mb-0">@devise($subventionRows->sum('reste'))</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">{{ __('finances.caisse_label') }}</small>
                    <h5 class="fw-bold mb-0">{{ $caisse?->reference ?? __('finances.caisse_non_active') }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm overflow-hidden">
        <div class="card-header theme-header p-4 border-0">
            <h5 class="fw-bold mb-0">{{ __('finances.eleves_subventionnes_title') }}</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4">{{ __('finances.th_eleve') }}</th>
                        <th>{{ __('eleves.label_matricule') }}</th>
                        <th>{{ __('finances.label_classe') }}</th>
                        <th>{{ __('finances.th_echeance') }}</th>
                        <th>{{ __('finances.th_date_limite') }}</th>
                        <th class="text-end">{{ __('finances.th_deja_paye') }}</th>
                        <th class="text-end px-4">{{ __('finances.th_reste_etat') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subventionRows as $row)
                        <tr>
                            <td class="px-4 fw-semibold">{{ $row->plan->eleve?->nom_eleve }} {{ $row->plan->eleve?->prenom_eleve }}</td>
                            <td><span class="badge bg-light text-dark">{{ $row->plan->eleve?->matricule ?: 'N/A' }}</span></td>
                            <td>{{ $row->plan->classe?->nom_classe }}</td>
                            <td>{{ $row->echeance->libelle }}</td>
                            <td>{{ $row->echeance->date_limite?->format('d/m/Y') }}</td>
                            <td class="text-end">@devise($row->deja_paye)</td>
                            <td class="text-end px-4 fw-bold text-warning">@devise($row->reste)</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">{{ __('finances.empty_subventions') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($caisse)
        <div class="modal fade" id="subventionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('finances.subventions-etat.store') }}" class="modal-content card theme-card">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('finances.encaisser_subvention_etat_title') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info border-0 border-start border-info border-4">
                            {{ __('finances.subvention_info_text') }}
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('finances.label_annee_payee_etat') }}</label>
                                <select name="annee_scolaire_id" class="form-select" required>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['annee_scolaire_id'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('finances.label_classe') }}</label>
                                <select name="classe_id" class="form-select">
                                    <option value="">{{ __('finances.all_classes_label') }}</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id_classe }}" @selected(($filters['classe_id'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('finances.label_date_reception') }}</label>
                                <input type="date" name="date_paiement" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('finances.label_montant_recu') }}</label>
                                <input type="number" name="montant_recu" class="form-control" min="1" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('finances.label_reference_etat') }}</label>
                                <input type="text" name="reference_etat" class="form-control" placeholder="{{ __('finances.reference_etat_placeholder') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('finances.label_observation') }}</label>
                                <input type="text" name="observation" class="form-control" placeholder="{{ __('finances.observation_placeholder') }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('finances.cancel_button') }}</button>
                        <button class="btn btn-success">{{ __('finances.save_and_distribute') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
