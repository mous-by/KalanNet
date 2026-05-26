@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Finances</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">Gestion financière</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Subventions État</li>
                </ol>
            </nav>
        </div>
        @if($caisse && (auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasAnyPermission(['subventions_etat_encaisser', 'paiements_faire'])))
            <div class="ms-auto">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#subventionModal">
                    <i class="bi bi-bank me-1"></i>Encaisser une subvention
                </button>
            </div>
        @endif
    </div>

    @include('finances.paiements.partials.alerts')

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('finances.subventions-etat') }}" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Année concernée</label>
                    <select name="annee_scolaire_id" class="form-select" onchange="this.form.submit()" required>
                        <option value="">Choisir...</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['annee_scolaire_id'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Classe</label>
                    <select name="classe_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" @selected(($filters['classe_id'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if(!$caisse)
        <div class="alert alert-warning border-0 border-start border-warning border-4">Aucune caisse active. Impossible d’encaisser une subvention.</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">Échéances ouvertes</small>
                    <h4 class="fw-bold mb-0">{{ $subventionRows->count() }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">Montant attendu État</small>
                    <h4 class="fw-bold text-warning mb-0">{{ number_format($subventionRows->sum('reste'), 0, ',', ' ') }} FCFA</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold">Caisse</small>
                    <h5 class="fw-bold mb-0">{{ $caisse?->reference ?? 'Non active' }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm overflow-hidden">
        <div class="card-header theme-header p-4 border-0">
            <h5 class="fw-bold mb-0">Élèves subventionnés non soldés</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4">Élève</th>
                        <th>Matricule</th>
                        <th>Classe</th>
                        <th>Échéance</th>
                        <th>Date limite</th>
                        <th class="text-end">Déjà payé</th>
                        <th class="text-end px-4">Reste État</th>
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
                            <td class="text-end">{{ number_format($row->deja_paye, 0, ',', ' ') }} FCFA</td>
                            <td class="text-end px-4 fw-bold text-warning">{{ number_format($row->reste, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Aucune créance État ouverte pour cette sélection.</td>
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
                        <h5 class="modal-title">Encaisser une subvention État</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info border-0 border-start border-info border-4">
                            Le montant reçu est global. KalanNet le répartira automatiquement sur les élèves subventionnés non soldés de l’année sélectionnée, même si l’État paie en retard.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Année payée par l’État</label>
                                <select name="annee_scolaire_id" class="form-select" required>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['annee_scolaire_id'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Classe</label>
                                <select name="classe_id" class="form-select">
                                    <option value="">Toutes les classes</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id_classe }}" @selected(($filters['classe_id'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date réception</label>
                                <input type="date" name="date_paiement" value="{{ now()->toDateString() }}" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Montant reçu</label>
                                <input type="number" name="montant_recu" class="form-control" min="1" step="0.01" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Référence État</label>
                                <input type="text" name="reference_etat" class="form-control" placeholder="Décision, virement, bordereau...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Observation</label>
                                <input type="text" name="observation" class="form-control" placeholder="Ex: paiement 2024 reçu en 2026">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-success">Enregistrer et répartir</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
