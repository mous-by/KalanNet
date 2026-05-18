@extends('layouts.app')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between bg-card p-4 rounded-4 shadow-sm">
                <div>
                    <h2 class="mb-1 fw-bold">Journal de Caisse</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">Finances</a></li>
                            <li class="breadcrumb-item active">Mouvements de caisse</li>
                        </ol>
                    </nav>
                </div>
                @if($caisse)
                    <div class="d-flex gap-2">
                        <button class="btn btn-danger rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#decaissementModal"><i class="bi bi-dash-lg me-2"></i>Sortie Caisse</button>
                        <button class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#encaissementModal"><i class="bi bi-plus-lg me-2"></i>Entrée Caisse</button>
                    </div>
                @endif
            </div>
        </div>
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
                            <h6 class="text-muted text-uppercase fw-bold small mb-3">Solde Initial</h6>
                            <h4 class="fw-bold mb-0 text-dark">{{ number_format($caisse->montant_initial, 0, ',', ' ') }} <small class="fs-6">FCFA</small></h4>
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
                            <h6 class="text-white-50 text-uppercase fw-bold small mb-3">Solde Actuel</h6>
                            <h2 class="fw-bold mb-0">{{ number_format($caisse->montant_net, 0, ',', ' ') }} <small class="fs-6">FCFA</small></h2>
                        </div>
                        <div class="widget-icon theme-icon-soft rounded-3">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-end">
            <div class="d-inline-block bg-light p-3 rounded-4 shadow-sm">
                <small class="text-muted d-block text-uppercase fw-bold">Référence Caisse</small>
                <span class="fs-4 fw-bold font-monospace text-primary">{{ $caisse->reference }}</span>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm overflow-hidden">
        <div class="card-header theme-header p-4 border-0">
            <h5 class="fw-bold mb-0">Journal des Mouvements</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3 border-0 small fw-bold text-muted text-uppercase">Date</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Type</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Motif / Libellé</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mouvements as $m)
                        <tr class="{{ $m->type == 'DEPENSE' ? 'table-danger-light' : '' }}">
                            <td class="px-4 py-3 small">{{ date('d/m/Y H:i', strtotime($m->date)) }}</td>
                            <td>
                                @if($m->type == 'RECETTE')
                                    <span class="badge bg-success-soft text-success rounded-pill px-3"><i class="bi bi-arrow-down-left me-1"></i> Entrée</span>
                                @else
                                    <span class="badge bg-danger-soft text-danger rounded-pill px-3"><i class="bi bi-arrow-up-right me-1"></i> Sortie</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">{{ $m->motif }}</div>
                                @if($m->type == 'RECETTE')
                                    <small class="text-muted">{{ $m->type_operation }}</small>
                                @endif
                            </td>
                            <td class="px-4 text-end fw-bold {{ $m->type == 'DEPENSE' ? 'text-danger' : 'text-success' }}">
                                {{ $m->type == 'DEPENSE' ? '-' : '+' }} {{ number_format($m->montant, 0, ',', ' ') }} FCFA
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-5 text-muted">Aucun mouvement enregistré.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="alert alert-warning rounded-4 border-0 shadow-sm p-4 text-center">
        <i class="bi bi-exclamation-triangle fs-1 mb-3 d-block"></i>
        <h4 class="fw-bold">Aucune caisse active</h4>
        <p class="mb-0">Veuillez configurer une caisse pour votre établissement dans les paramètres.</p>
        <button class="btn btn-primary mt-3 px-4" data-bs-toggle="modal" data-bs-target="#caisseModal" style="background-color: var(--theme-accent) !important; border-color: var(--theme-accent) !important; color: white !important;">
            <i class="bi bi-plus-lg me-2"></i>Créer la caisse
        </button>
    </div>
    @endif

    <div class="modal fade" id="caisseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title">Enregistrement de caisse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('finances.caisse.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Libellé caisse <span class="text-danger">*</span></label>
                            <input type="text" name="libelle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Montant initial <span class="text-danger">*</span></label>
                            <input type="number" name="montant_initial" class="form-control" min="0" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Statut</label>
                            <select name="status" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
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
                        <h5 class="modal-title">Nouvelle entrée caisse</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <form method="POST" action="{{ route('finances.encaissements.store') }}">
                        @csrf
                        <input type="hidden" name="id_caisse" value="{{ $caisse->id_caisse }}">
                        <div class="modal-body">
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
                                    <label class="form-label">Type opération <span class="text-danger">*</span></label>
                                    <select name="type_operation" class="form-select" required>
                                        <option value="encaissement divers">Encaissement divers</option>
                                        <option value="encaissement scolaire">Encaissement scolaire</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_encaissement" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Motif <span class="text-danger">*</span></label>
                                    <input type="text" name="motif_encaissement" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Montant <span class="text-danger">*</span></label>
                                    <input type="number" name="montant_encaissement" class="form-control" min="1" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="decaissementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content card theme-card">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvelle sortie caisse</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <form method="POST" action="{{ route('finances.decaissements.store') }}">
                        @csrf
                        <input type="hidden" name="id_caisse" value="{{ $caisse->id_caisse }}">
                        <div class="modal-body">
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
                            <button type="submit" class="btn btn-primary">Envoyer</button>
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
</style>
@endsection
