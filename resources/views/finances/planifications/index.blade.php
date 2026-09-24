@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $isPublicSchool ? __('finances.menu_planifications_public') : __('finances.menu_planifications_private') }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">{{ __('finances.title') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $isPublicSchool ? __('finances.liste_cooperatives') : __('finances.liste_planifications_paiements') }}</li>
            </ol>
        </nav>
    </div>
    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('finances_planifications_creation'))
        <a href="{{ route('finances.planifications.create') }}" class="btn border-0 border-primary border-4 bg-light-primary text-primary">
            <i class="bi bi-plus-lg"></i> {{ __('finances.add_generic') }}
        </a>
    @endif
</div>

@php
    $canEdit = auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('finances_planifications_modification');
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-12 col-md-3">
        @include('finances.partials.menu', ['active' => 'planifications'])
    </div>

    <div class="col-12 col-lg-9">
        <div class="card theme-card w-100">
            <div class="card-header theme-header">
                <i class="bi bi-cash-coin me-1"></i>
                {{ $isPublicSchool ? __('finances.liste_cooperatives') : __('finances.liste_planifications') }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('finances.planifications.filter') }}" class="row g-3" id="planificationFilterForm" data-auto-filter="true">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label" for="id_classe">{{ __('finances.label_classe') }}</label>
                        <select class="single-select form-select auto-submit-planification" id="id_classe" name="id_classe">
                            <option value="">{{ __('finances.select_classe') }}</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" @selected(($filters['id_classe'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="id_annee">{{ __('eleves.label_annee') }}</label>
                        <select class="single-select form-select auto-submit-planification" id="id_annee" name="id_annee">
                            <option value="">{{ __('finances.select_annee') }}</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="card theme-card w-100 mt-4">
            <div class="card-body">
                <div class="table-responsive mt-3 mb-3">
                    <table class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ $isPublicSchool ? __('finances.th_cooperative') : __('finances.th_motif') }}</th>
                                <th>{{ $isPublicSchool ? __('finances.th_montant_cooperative') : __('finances.th_cout_total') }}</th>
                                <th class="text-center">{{ __('classes.th_action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($planifications as $planification)
                                <tr>
                                    <td>
                                        {{ $isPublicSchool ? __('finances.th_cooperative') : $planification->motif }}
                                        @if($planification->tranches->isNotEmpty())
                                            <ul class="list-unstyled small text-muted mb-0 mt-1">
                                                @foreach($planification->tranches as $tranche)
                                                    @php
                                                        $trancheMontantFmt = number_format((float) $tranche->montant, 0, ',', ' ');
                                                        $trancheDateFmt = $tranche->date_limite->format('d/m/Y');
                                                    @endphp
                                                    <li>{{ __('finances.tranche_montant_avant_le', ['libelle' => $tranche->libelle, 'montant' => $trancheMontantFmt, 'date' => $trancheDateFmt]) }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                    <td>@devise((float) $planification->montant_planification)</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots"></i>
                                            </a>
                                            <div class="dropdown-menu">
                                                @if($canEdit)
                                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editPlanificationModal{{ $planification->id_planification }}">
                                                        <i class="bi bi-pencil me-1"></i> {{ __('finances.edit_button') }}
                                                    </button>
                                                @endif
                                                @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('finances_planifications_supprimer'))
                                                    <form method="POST" action="{{ route('finances.planifications.destroy', $planification->id_planification) }}"
                                                          data-confirm-delete
                                                          data-confirm-title="{{ $isPublicSchool ? __('finances.confirm_delete_cooperative') : __('finances.confirm_delete_planification') }}"
                                                          data-confirm-text="{{ __('finances.confirm_delete_irreversible') }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-1"></i> {{ __('classes.remove') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">{{ $isPublicSchool ? __('finances.empty_cooperatives') : __('finances.empty_planifications') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canEdit)
    @foreach($planifications as $planification)
        @php
            $tranches = $planification->tranches;
            $linked = (int) ($linkedCounts[$planification->id_planification] ?? 0);
        @endphp
        <div class="modal fade" id="editPlanificationModal{{ $planification->id_planification }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered {{ $tranches->isNotEmpty() ? 'modal-lg' : '' }}">
                <div class="modal-content border-0 rounded-4 shadow">
                    <form method="POST" action="{{ route('finances.planifications.update', $planification->id_planification) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header theme-header">
                            <h5 class="modal-title fw-bold">{{ __('finances.modifier_prefix', ['motif' => $isPublicSchool ? __('finances.th_cooperative') : $planification->motif]) }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('eleves.close') }}"></button>
                        </div>
                        <div class="modal-body">
                            @if($linked > 0)
                                <div class="alert alert-info py-2 small">
                                    {{ __('finances.linked_students_info', ['count' => $linked]) }}
                                </div>
                            @endif

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('finances.label_date_debut') }}</label>
                                    <input type="date" name="date_debut" class="form-control" value="{{ $planification->date_debut }}" required>
                                </div>

                                @if($tranches->isEmpty())
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('finances.label_date_fin') }}</label>
                                        <input type="date" name="date_fin" class="form-control" value="{{ $planification->date_fin }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ $isPublicSchool ? __('finances.th_montant_cooperative') : __('finances.th_cout_total') }} ({{ \App\Support\Devise::symbole() }})</label>
                                        <input type="number" name="montant_planification" class="form-control" min="1" value="{{ (float) $planification->montant_planification }}" required>
                                    </div>
                                @else
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                <thead>
                                                    <tr><th style="width: 30%;">{{ __('finances.th_tranche') }}</th><th style="width: 35%;">{{ __('finances.label_montant') }} ({{ \App\Support\Devise::symbole() }})</th><th style="width: 35%;">{{ __('finances.th_date_limite') }}</th></tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($tranches as $tranche)
                                                        <tr>
                                                            <td>{{ $tranche->libelle }}</td>
                                                            <td><input type="number" name="tranche_montant[]" class="form-control form-control-sm edit-tranche-montant" min="1" value="{{ (float) $tranche->montant }}" required></td>
                                                            <td><input type="date" name="tranche_date[]" class="form-control form-control-sm" value="{{ $tranche->date_limite->format('Y-m-d') }}" required></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th>{{ __('finances.total_label') }}</th>
                                                        <th colspan="2" class="edit-tranche-total">@devise((float) $planification->montant_planification)</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                        <div class="form-text">{{ __('finances.tranches_total_help') }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('finances.cancel_button') }}</button>
                            <button type="submit" class="btn btn-primary fw-bold">{{ __('finances.save_button') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif
@push('scripts')
@php
    $planifIndexI18n = [
        'confirmDeleteDefault' => __('finances.confirm_delete_default'),
        'confirmYesDelete' => __('finances.confirm_yes_delete'),
        'cancel' => __('finances.cancel_button'),
        'deviseSymbole' => \App\Support\Devise::symbole(),
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const i18n = @json($planifIndexI18n);
    document.querySelectorAll('[data-confirm-delete]').forEach(function (deleteForm) {
        deleteForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const title = deleteForm.dataset.confirmTitle || i18n.confirmDeleteDefault;
            const text = deleteForm.dataset.confirmText || '';
            if (!window.Swal) {
                if (confirm(title)) deleteForm.submit();
                return;
            }
            Swal.fire({
                title,
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: i18n.confirmYesDelete,
                cancelButtonText: i18n.cancel,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
            }).then(function (result) {
                if (result.isConfirmed) deleteForm.submit();
            });
        });
    });

    document.querySelectorAll('.edit-tranche-montant').forEach(function (input) {
        input.addEventListener('input', function () {
            const modal = input.closest('.modal');
            let sum = 0;
            modal.querySelectorAll('.edit-tranche-montant').forEach(function (field) {
                sum += parseFloat(field.value) || 0;
            });
            modal.querySelector('.edit-tranche-total').textContent = sum.toLocaleString('fr-FR') + ' ' + i18n.deviseSymbole;
        });
    });

    const form = document.getElementById('planificationFilterForm');
    document.querySelectorAll('.auto-submit-planification').forEach(function (field) {
        field.addEventListener('change', function () {
            const classe = document.getElementById('id_classe')?.value;
            const annee = document.getElementById('id_annee')?.value;
            if (classe && annee) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endsection
