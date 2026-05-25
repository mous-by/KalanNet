@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Finances</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('finances.paiements') }}">Paiements élèves</a></li>
                <li class="breadcrumb-item active" aria-current="page">Historique</li>
            </ol>
        </nav>
    </div>
    <div class="ms-auto">
        <a href="{{ route('finances.paiements') }}" class="btn theme-outline-btn">
            <i class="bi bi-arrow-left me-1"></i>Retour
        </a>
    </div>
</div>

<form method="POST" action="{{ route('finances.paiements.historique') }}" class="card theme-card mb-4" id="historyFilterForm" data-auto-filter="true">
    @csrf
    <div class="card-body row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Classe</label>
            <select name="classe_id" class="form-select auto-submit">
                <option value="">Toutes</option>
                @foreach($classes as $classe)
                    <option value="{{ $classe->id_classe }}" @selected(($filters['classe_id'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Année</label>
            <select name="annee_scolaire_id" class="form-select auto-submit">
                <option value="">Toutes</option>
                @foreach($annees as $annee)
                    <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['annee_scolaire_id'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select auto-submit">
                <option value="">Tous</option>
                <option value="valide" @selected(($filters['statut'] ?? '') === 'valide')>Valide</option>
                <option value="annule" @selected(($filters['statut'] ?? '') === 'annule')>Annulé</option>
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
                    <th>Date</th>
                    <th>Référence</th>
                    <th>Reçu</th>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>Motif</th>
                    <th class="text-end">Montant</th>
                    <th>Payeur</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($paiements as $paiement)
                <tr>
                    <td>{{ optional($paiement->date_paiement)->format('d/m/Y') }}</td>
                    <td>{{ $paiement->reference }}</td>
                    <td>{{ $paiement->numero_recu }}</td>
                    <td class="fw-bold">{{ $paiement->eleve?->nom_eleve }} {{ $paiement->eleve?->prenom_eleve }}</td>
                    <td>{{ $paiement->classe?->nom_classe }}</td>
                    <td>{{ $paiement->motif }}</td>
                    <td class="text-end fw-bold">{{ number_format((float) ($paiement->montant_paye ?? $paiement->montant), 0, ',', ' ') }} F</td>
                    <td>{{ $paiement->nom_payeur }}</td>
                    <td class="text-center">
                        <div class="btn-group">
                            <a href="{{ route('finances.paiements.download', $paiement->id_paiement) }}" class="btn btn-sm history-action-btn history-action-receipt" title="Reçu PDF" aria-label="Reçu PDF">
                                <i class="bi bi-receipt"></i>
                            </a>
                            <a href="{{ route('finances.paiements.thermique', $paiement->id_paiement) }}" class="btn btn-sm history-action-btn history-action-print" title="Reçu thermique" aria-label="Reçu thermique">
                                <i class="bi bi-printer"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-5">Aucun historique trouvé.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($paiements->hasPages())
        <div class="card-footer bg-white">{{ $paiements->links() }}</div>
    @endif
</div>

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
});
</script>
@endpush
@endsection
