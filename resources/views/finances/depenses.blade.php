@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Finances</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">Gestion financière</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Dépenses</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('finances.paiements.partials.alerts')

    @if(!$caisse)
        <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4">
            <h5 class="fw-bold mb-1">Aucune caisse active</h5>
            <p class="mb-0">Créez ou activez une caisse avant d’enregistrer des dépenses.</p>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Caisse</small>
                        <h5 class="fw-bold mb-0">{{ $caisse->reference }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">Solde actuel</small>
                        <h4 class="fw-bold text-primary mb-0">{{ number_format($caisse->montant_net, 0, ',', ' ') }} FCFA</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <small class="text-muted text-uppercase fw-bold">En attente</small>
                        <h4 class="fw-bold text-warning mb-0">{{ $depenses->where('valide', false)->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm overflow-hidden">
            <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2 p-4 border-0">
                <h5 class="fw-bold mb-0">Liste des dépenses</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if($caisse && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('decaissements_creation')))
                        <button class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white" 
                                style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                data-bs-toggle="modal" data-bs-target="#decaissementModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>Nouvelle dépense</span>
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="py-3">Motif</th>
                                <th class="py-3">Demandé par</th>
                                <th class="py-3 text-end">Montant</th>
                                <th class="py-3">Statut</th>
                                <th class="py-3">Validé par</th>
                                <th class="px-4 py-3 text-end">Action</th>
                            </tr>
                        </thead>
                    <tbody>
                        @forelse($depenses as $depense)
                            <tr id="decaissement-{{ $depense->id_decaissement }}">
                                <td class="px-4 py-3">{{ optional($depense->date_decaissement)->format('d/m/Y') }}</td>
                                <td class="fw-semibold py-3">{{ $depense->motif_decaissement }}</td>
                                <td>{{ $depense->utilisateur?->nomPrenom ?? 'Utilisateur' }}</td>
                                <td class="text-end fw-bold {{ $depense->valide ? 'text-danger' : 'text-warning' }}">
                                    {{ $depense->valide ? '-' : '' }} {{ number_format($depense->montant_decaissement, 0, ',', ' ') }} FCFA
                                </td>
                                <td>
                                    <span class="badge bg-{{ $depense->valide ? 'success' : 'warning' }} rounded-pill px-3">
                                        {{ $depense->valide ? 'Validée' : 'En attente' }}
                                    </span>
                                </td>
                                <td>
                                    @if($depense->valide)
                                        {{ $depense->validateur?->nomPrenom ?? 'Validation directe' }}
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
                                                <i class="bi bi-check2-circle me-1"></i>Valider
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">Aucune dépense enregistrée.</td>
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
                        <h5 class="modal-title">Nouvelle dépense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <form method="POST" action="{{ route('finances.decaissements.store') }}">
                        @csrf
                        <input type="hidden" name="id_caisse" value="{{ $caisse->id_caisse }}">
                        <div class="modal-body">
                            <div class="alert alert-info border-0 border-start border-info border-4">
                                Si vous n’avez pas la permission de validation, la dépense sera soumise en attente et ne diminuera la caisse qu’après validation.
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Caisse référence</label>
                                    <input type="text" class="form-control" value="{{ $caisse->reference }} ({{ number_format($caisse->montant_net, 0, ',', ' ') }} FCFA)" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Année scolaire <span class="text-danger">*</span></label>
                                    <select name="id_annee_scolaire" class="form-select" required>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_decaissement" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Motif <span class="text-danger">*</span></label>
                                    <input type="text" name="motif_decaissement" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Montant <span class="text-danger">*</span></label>
                                    <input type="number" name="montant_decaissement" class="form-control" min="1" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Soumettre</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.querySelectorAll('.js-validate-decaissement').forEach((form) => {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    Swal.fire({
                        title: 'Valider cette dépense ?',
                        text: 'Le montant sera déduit de la caisse.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Oui, valider',
                        cancelButtonText: 'Annuler',
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
