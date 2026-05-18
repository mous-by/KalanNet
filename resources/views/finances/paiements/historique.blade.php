@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1 fw-bold">Historique des paiements élèves</h2>
        <div class="text-muted">Paiements enregistrés, reçus et exports.</div>
    </div>
    <a href="{{ route('finances.paiements') }}" class="btn btn-primary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<form method="POST" action="{{ route('finances.paiements.historique') }}" class="card theme-card mb-4" id="historyFilterForm">
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
                            <a href="{{ route('finances.paiements.download', $paiement->id_paiement) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-receipt"></i>
                            </a>
                            <a href="{{ route('finances.paiements.thermique', $paiement->id_paiement) }}" class="btn btn-sm btn-primary">
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
