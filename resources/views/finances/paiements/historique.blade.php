@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ __('finances.title') }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('finances.paiements') }}">{{ __('finances.breadcrumb_paiements_eleves') }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_historique') }}</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('finances.paiements') }}" class="btn theme-outline-btn">
            <i class="bi bi-arrow-left me-1"></i>{{ __('finances.back_button') }}
        </a>
    </div>
</div>

<div class="row g-0">
<div class="col-12 col-md-3">
    @include('finances.partials.menu', ['active' => 'historique'])
</div>

<div class="col-12 col-md-9 pt-4 pt-md-0 p-md-3">
@php
    // Accordée par le SupAdmin seulement (voir Permission::JAMAIS_PAR_DEFAUT).
    $peutAnnuler = auth()->user()->userHasPermission('paiements_annuler');
@endphp
@include('finances.paiements.partials.alerts')
<form method="POST" action="{{ route('finances.paiements.historique') }}" class="card theme-card mb-4" id="historyFilterForm" data-auto-filter="true">
    @csrf
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">{{ __('finances.label_classe') }}</label>
            <select name="classe_id" class="form-select auto-submit">
                <option value="">{{ __('finances.all_classes') }}</option>
                @foreach($classes as $classe)
                    <option value="{{ $classe->id_classe }}" @selected(($filters['classe_id'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">{{ __('finances.label_annee') }}</label>
            <select name="annee_scolaire_id" class="form-select auto-submit">
                <option value="">{{ __('finances.all_annees') }}</option>
                @foreach($annees as $annee)
                    <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['annee_scolaire_id'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">{{ __('finances.label_statut') }}</label>
            <select name="statut" class="form-select auto-submit">
                <option value="">{{ __('finances.all_statuts') }}</option>
                <option value="valide" @selected(($filters['statut'] ?? '') === 'valide')>{{ __('finances.statut_valide') }}</option>
                <option value="annule" @selected(($filters['statut'] ?? '') === 'annule')>{{ __('finances.statut_annule') }}</option>
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2 justify-content-end">
            <a class="btn btn-outline-secondary" href="{{ route('finances.paiements.historique.export', ['format' => 'csv']) }}">CSV</a>
            <a class="btn btn-outline-secondary" href="{{ route('finances.paiements.historique.export', ['format' => 'xlsx']) }}">XLSX</a>
            <a class="btn btn-outline-secondary" href="{{ route('finances.paiements.historique.export', ['format' => 'pdf']) }}">PDF</a>
        </div>
    </div>
</form>

<div class="card theme-card overflow-hidden">
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th>{{ __('finances.th_date') }}</th>
                    <th>{{ __('finances.th_reference') }}</th>
                    <th>{{ __('finances.th_recu') }}</th>
                    <th>{{ __('finances.th_eleve') }}</th>
                    <th>{{ __('finances.label_classe') }}</th>
                    <th>{{ __('finances.th_motif') }}</th>
                    <th class="text-end">{{ __('finances.th_montant') }}</th>
                    <th>{{ __('finances.th_payeur') }}</th>
                    <th class="text-center">{{ __('finances.th_actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($paiements as $paiement)
                <tr>
                    <td>{{ optional($paiement->date_paiement)->format('d/m/Y') }}</td>
                    <td>{{ $paiement->reference }}</td>
                    <td>{{ $paiement->numero_recu }}</td>
                    <td class="fw-bold">{{ $paiement->eleve?->prenom_eleve }} {{ $paiement->eleve?->nom_eleve }}</td>
                    <td>{{ $paiement->classe?->nom_classe }}</td>
                    <td>{{ $paiement->motif }}</td>
                    <td class="text-end fw-bold">@devise((float) ($paiement->montant_paye ?? $paiement->montant))</td>
                    <td>{{ $paiement->nom_payeur }}</td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="{{ route('finances.paiements.download', $paiement->id_paiement) }}" class="btn btn-sm history-action-btn history-action-receipt" title="{{ __('finances.receipt_pdf_title') }}" aria-label="{{ __('finances.receipt_pdf_title') }}">
                                <i class="bi bi-receipt"></i>
                            </a>
                            <a href="{{ route('finances.paiements.thermique', $paiement->id_paiement) }}" class="btn btn-sm history-action-btn history-action-print" title="{{ __('finances.receipt_thermal_title') }}" aria-label="{{ __('finances.receipt_thermal_title') }}">
                                <i class="bi bi-printer"></i>
                            </a>
                            @if($paiement->statut === 'annule')
                                <span class="badge bg-danger align-self-center ms-1" title="{{ $paiement->motif_annulation }}">{{ __('finances.statut_annule') }}</span>
                            @elseif($peutAnnuler)
                                <button type="button" class="btn btn-sm history-action-btn history-action-cancel" title="{{ __('finances.annuler_paiement') }}" aria-label="{{ __('finances.annuler_paiement') }}"
                                    data-bs-toggle="modal" data-bs-target="#annulerPaiementModal"
                                    data-action="{{ route('finances.paiements.cancel', $paiement->id_paiement) }}"
                                    data-libelle="{{ $paiement->eleve?->prenom_eleve }} {{ $paiement->eleve?->nom_eleve }} — @devise((float) ($paiement->montant_paye ?? $paiement->montant)) — {{ __('finances.th_recu') }} {{ $paiement->numero_recu }}">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-5">{{ __('finances.empty_history') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($paiements->hasPages())
        <div class="card-footer bg-white">{{ $paiements->links() }}</div>
    @endif
</div>
</div>
</div>

@if($peutAnnuler)
<div class="modal fade" id="annulerPaiementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" id="annulerPaiementForm">
            @csrf @method('DELETE')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('finances.annuler_paiement') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('configuration.fermer') }}"></button>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-2" id="annulerPaiementLibelle"></p>
                <p class="small text-muted">{{ __('finances.annuler_paiement_aide') }}</p>
                <label class="form-label" for="motif_annulation">{{ __('finances.annuler_paiement_motif') }}</label>
                <textarea class="form-control" name="motif_annulation" id="motif_annulation" rows="2" maxlength="255" required minlength="5"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('configuration.fermer') }}</button>
                <button type="submit" class="btn btn-danger">{{ __('finances.annuler_paiement_confirmer') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('styles')
<style>
    .history-action-btn {
        background: #ffffff !important;
        border: 1px solid #d8e2ee !important;
        color: #0f172a !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .08);
    }
    .history-action-btn i {
        opacity: 1 !important;
    }
    .history-action-receipt i {
        color: #0f766e !important;
    }
    .history-action-print i {
        color: #1d4ed8 !important;
    }
    .history-action-cancel i {
        color: #dc2626 !important;
    }
    .history-action-receipt:hover,
    .history-action-receipt:focus {
        background: #ecfdf5 !important;
        border-color: #99f6e4 !important;
    }
    .history-action-print:hover,
    .history-action-print:focus {
        background: #eff6ff !important;
        border-color: #bfdbfe !important;
    }
    .history-action-btn:hover i,
    .history-action-btn:focus i {
        opacity: 1 !important;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('historyFilterForm');
    document.querySelectorAll('#historyFilterForm .auto-submit').forEach(function (field) {
        field.addEventListener('change', function () {
            form.submit();
        });
    });
    const modal = document.getElementById('annulerPaiementModal');
    modal?.addEventListener('show.bs.modal', function (event) {
        const bouton = event.relatedTarget;
        document.getElementById('annulerPaiementForm').action = bouton.dataset.action;
        document.getElementById('annulerPaiementLibelle').textContent = bouton.dataset.libelle;
        document.getElementById('motif_annulation').value = '';
    });
});
</script>
@endpush
@endsection
