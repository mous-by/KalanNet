@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <a href="{{ route('enseignants.index') }}" class="btn btn-light rounded-circle p-2 me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h2 class="mb-1 fw-bold">Profil de l'Enseignant</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('enseignants.index') }}">Enseignants</a></li>
                                <li class="breadcrumb-item active">{{ $enseignant->nom_prenom_enseignant }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-light px-4" data-bs-toggle="modal" data-bs-target="#teacherBadgeModal"><i class="bi bi-person-vcard me-2"></i>Badge</button>
                    <a href="{{ route('enseignants.edit', $enseignant->id_enseignant) }}" class="btn btn-light px-4"><i class="bi bi-pencil me-2"></i>Modifier</a>
                    <button type="button" class="btn btn-primary px-4" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimer Fiche</button>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Profile -->
        <div class="col-lg-4">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-header theme-header">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2"></i>Identité</h5>
                </div>
                <div class="card-body p-4 text-center">
                    <div class="avatar-xl mx-auto mb-4 position-relative" style="width: 150px; height: 150px;">
                        @if($enseignant->avatar_enseignant)
                            <img src="{{ asset($enseignant->avatar_enseignant) }}" alt="" class="rounded-circle w-100 h-100 object-fit-cover border border-4 border-white shadow">
                        @else
                            <div class="bg-info-soft text-info rounded-circle w-100 h-100 d-flex align-items-center justify-content-center fs-1 fw-bold">
                                {{ strtoupper(substr($enseignant->nom_prenom_enseignant, 0, 1)) }}
                            </div>
                        @endif
                        <span class="position-absolute bottom-0 end-0 bg-success border border-white border-3 rounded-circle p-2" title="Actif"></span>
                    </div>
                    <h4 class="fw-bold mb-1">{{ $enseignant->nom_prenom_enseignant }}</h4>
                    <p class="text-muted mb-2">{{ $enseignant->diplome_enseignant }}</p>
                    <div class="mb-4">
                        <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2">{{ $enseignant->specialite ?: 'Polyvalent' }}</span>
                        <span class="badge bg-light text-dark border px-3 py-2">{{ $enseignant->matricule ?: 'Matricule non défini' }}</span>
                    </div>
                    
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="p-3 theme-icon-soft rounded-3">
                                <h6 class="mb-0 fw-bold">{{ $enseignant->type_contrat_enseignant }}</h6>
                                <small class="text-muted">Contrat</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 theme-icon-soft rounded-3">
                                <h6 class="mb-0 fw-bold">{{ $enseignant->genre_enseignant }}</h6>
                                <small class="text-muted">Genre</small>
                            </div>
                        </div>
                    </div>

                    <div class="text-start">
                        <h6 class="fw-bold mb-3 small text-uppercase text-muted">Coordonnées</h6>
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box theme-icon-soft rounded-3 p-2 me-3"><i class="bi bi-envelope text-primary"></i></div>
                            <div>
                                <small class="text-muted d-block">Email</small>
                                <span class="fw-bold">{{ $enseignant->email_enseignant }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box theme-icon-soft rounded-3 p-2 me-3"><i class="bi bi-telephone text-primary"></i></div>
                            <div>
                                <small class="text-muted d-block">Téléphone</small>
                                <span class="fw-bold">{{ $enseignant->telephone_enseignant }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="icon-box theme-icon-soft rounded-3 p-2 me-3"><i class="bi bi-geo-alt text-primary"></i></div>
                            <div>
                                <small class="text-muted d-block">Adresse</small>
                                <span class="fw-bold">{{ $enseignant->lieu_naissance_enseignant ?: 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Tabs -->
        <div class="col-lg-8">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-header theme-header p-4">
                    <ul class="nav nav-pills card-header-pills" id="teacherTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active px-4 theme-pill-active" data-bs-toggle="pill" data-bs-target="#info">Informations</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4" data-bs-toggle="pill" data-bs-target="#classes">Classes & Matières</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4" data-bs-toggle="pill" data-bs-target="#emargements">Émargements</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4" data-bs-toggle="pill" data-bs-target="#programme">Programme</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4" data-bs-toggle="pill" data-bs-target="#presences">Présences</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="info">
                            <h5 class="fw-bold mb-4">Détails Personnels & Professionnels</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="text-muted small text-uppercase fw-bold d-block mb-1">Date de Naissance</label>
                                    <p class="fw-bold text-dark">{{ $enseignant->date_naissance_enseignant ?: 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small text-uppercase fw-bold d-block mb-1">Matricule</label>
                                    <p class="fw-bold text-dark">{{ $enseignant->matricule ?: 'N/A' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small text-uppercase fw-bold d-block mb-1">Spécialité</label>
                                    <p class="fw-bold text-dark">{{ $enseignant->specialite ?: 'Polyvalent' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small text-uppercase fw-bold d-block mb-1">Ancienneté</label>
                                    <p class="fw-bold text-dark">{{ $enseignant->anciennete_annees }} ans</p>
                                </div>
                            </div>

                            @if($enseignant->type_contrat_enseignant === 'FONCTIONNAIRE')
                            <h5 class="fw-bold mb-4 mt-5">Informations Administratives (Public)</h5>
                            <div class="theme-icon-soft p-4 rounded-3 border">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold d-block mb-1">Service Employeur</label>
                                        <p class="fw-bold text-dark mb-0">{{ $enseignant->service_employeur }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold d-block mb-1">Statut Matrimonial</label>
                                        <p class="fw-bold text-dark mb-0">{{ $enseignant->statut_matrimonial }} ({{ $enseignant->nombre_enfants }} enfants)</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="tab-pane fade" id="classes">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Classe</th>
                                            <th>Matière</th>
                                            <th>Coefficient</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($lignesClasses as $ligne)
                                            <tr>
                                                <td class="fw-bold">{{ $ligne->classe->nom_classe ?? 'N/A' }}</td>
                                                <td>{{ $ligne->matiere->nom_matiere ?? 'N/A' }}</td>
                                                <td>{{ number_format($ligne->coefficient, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center py-4 text-muted">Aucune affectation pédagogique trouvée.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="emargements">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Total</small>
                                        <h4 class="mb-0 fw-bold">{{ $emargementStats['total'] }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Validés</small>
                                        <h4 class="mb-0 fw-bold">{{ $emargementStats['valides'] }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Heures totales</small>
                                        <h4 class="mb-0 fw-bold">{{ number_format($emargementStats['heures'], 2) }}</h4>
                                    </div>
                                </div>
                            </div>

                            @if($vctPayment['eligible'])
                                <div class="alert alert-info border-0 border-start border-info border-4">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                        <div>
                                            <div class="fw-bold">Base de paiement VCT</div>
                                            <div class="small text-muted">{{ number_format($vctPayment['heures_validees'], 2, ',', ' ') }} heure(s) validée(s) x {{ number_format($vctPayment['prix_heure'], 0, ',', ' ') }} FCFA</div>
                                        </div>
                                        <div class="h5 fw-bold mb-0">{{ number_format($vctPayment['montant'], 0, ',', ' ') }} FCFA</div>
                                    </div>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Classe</th>
                                            <th>Matière</th>
                                            <th>Leçon</th>
                                            <th>Heures</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentEmargements as $emargement)
                                            <tr>
                                                <td>{{ optional($emargement->date_emargement)->format('d/m/Y H:i') ?: 'N/A' }}</td>
                                                <td>{{ $emargement->classe->nom_classe ?? 'N/A' }}</td>
                                                <td>{{ $emargement->matiere->nom_matiere ?? 'N/A' }}</td>
                                                <td>{{ $emargement->lecon->titre ?? 'N/A' }}</td>
                                                <td>{{ $emargement->nombre_heure }}</td>
                                                <td>
                                                    <span class="badge theme-icon-soft">
                                                        {{ $emargement->valide ? 'Validé' : 'En attente' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">Aucun émargement trouvé.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="{{ route('enseignants.emargements', ['id_enseignant' => $enseignant->id_enseignant]) }}" class="btn theme-pill-active px-4">
                                    Voir tous les émargements
                                </a>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="programme">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Affectations</small>
                                        <h4 class="mb-0 fw-bold">{{ $programmeProgress->count() }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Leçons validées</small>
                                        <h4 class="mb-0 fw-bold">{{ $programmeProgress->sum('completed') }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Leçons prévues</small>
                                        <h4 class="mb-0 fw-bold">{{ $programmeProgress->sum('total') }}</h4>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Classe</th>
                                            <th>Matière</th>
                                            <th>Progression</th>
                                            <th>Prochaine leçon</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($programmeProgress as $row)
                                            <tr>
                                                <td class="fw-bold">{{ $row['classe']->nom_classe ?? 'N/A' }}</td>
                                                <td>{{ $row['matiere']->nom_matiere ?? 'N/A' }}</td>
                                                <td style="min-width: 210px;">
                                                    <div class="d-flex justify-content-between small mb-1">
                                                        <span>{{ $row['completed'] }}/{{ $row['total'] }} leçon(s)</span>
                                                        <span class="fw-bold">{{ $row['percent'] }}%</span>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar" style="width: {{ $row['percent'] }}%;"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($row['next'])
                                                        {{ trim(($row['next']->numero ? $row['next']->numero.' - ' : '').$row['next']->titre) }}
                                                    @else
                                                        <span class="text-success fw-bold">Programme terminé</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">Aucune progression disponible.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="presences">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Total</small>
                                        <h4 class="mb-0 fw-bold">{{ $presenceStats['total'] }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Validées</small>
                                        <h4 class="mb-0 fw-bold">{{ $presenceStats['valides'] }}</h4>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="theme-icon-soft rounded-3 p-3">
                                        <small class="text-muted text-uppercase fw-bold">Heures</small>
                                        <h4 class="mb-0 fw-bold">{{ number_format($presenceStats['heures'], 2) }}</h4>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Classe</th>
                                            <th>Leçons</th>
                                            <th>Heures</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentPresences as $presence)
                                            <tr>
                                                <td>{{ optional($presence->date_presence)->format('d/m/Y H:i') ?: 'N/A' }}</td>
                                                <td>{{ $presence->classe->nom_classe ?? 'N/A' }}</td>
                                                <td>
                                                    @forelse($presence->lecons as $lecon)
                                                        <span class="d-block">{{ $lecon->titre }}</span>
                                                    @empty
                                                        <span class="text-muted">Aucune leçon</span>
                                                    @endforelse
                                                </td>
                                                <td>{{ number_format($presence->nombre_heure, 2) }}</td>
                                                <td>
                                                    <span class="badge theme-icon-soft">
                                                        {{ $presence->valide ? 'Validée' : 'En attente' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">Aucune présence trouvée.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="{{ route('enseignants.presences', ['id_enseignant' => $enseignant->id_enseignant]) }}" class="btn theme-pill-active px-4">
                                    Voir toutes les présences
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="teacherBadgeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header theme-header">
                <h5 class="modal-title fw-bold">Badge professionnelle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="teacher-badge bg-white mx-auto overflow-hidden">
                    <div class="teacher-badge-header text-center py-3">
                        <div class="small text-uppercase fw-bold">{{ session('nomEcole') ?: ($enseignant->ecole->nomEcole ?? 'École') }}</div>
                        <div class="fw-bold">BADGE ENSEIGNANT</div>
                    </div>
                    <div class="p-4 text-center">
                        <div class="mx-auto mb-3 rounded-circle overflow-hidden border border-3 border-white shadow" style="width: 110px; height: 110px;">
                            @if($enseignant->avatar_enseignant)
                                <img src="{{ asset($enseignant->avatar_enseignant) }}" alt="" class="w-100 h-100 object-fit-cover">
                            @else
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light fs-1 fw-bold">
                                    {{ strtoupper(substr($enseignant->nom_prenom_enseignant, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <h5 class="fw-bold mb-1">{{ $enseignant->nom_prenom_enseignant }}</h5>
                        <div class="text-muted mb-3">{{ $enseignant->specialite ?: 'Enseignant polyvalent' }}</div>
                        <div class="row g-2 text-start small">
                            <div class="col-6">
                                <div class="border rounded-3 p-2">
                                    <span class="text-muted d-block">Matricule</span>
                                    <strong>{{ $enseignant->matricule ?: 'N/A' }}</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded-3 p-2">
                                    <span class="text-muted d-block">Contrat</span>
                                    <strong>{{ $enseignant->type_contrat_enseignant }}</strong>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="border rounded-3 p-2">
                                    <span class="text-muted d-block">Contact</span>
                                    <strong>{{ $enseignant->telephone_enseignant ?: 'N/A' }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="teacher-badge-footer px-4 py-2 small d-flex justify-content-between">
                        <span>Statut : {{ $enseignant->is_deleted ? 'Archivé' : 'Actif' }}</span>
                        <span>{{ now()->format('Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary px-4" onclick="window.print()">Imprimer</button>
            </div>
        </div>
    </div>
</div>

<style>
    .teacher-badge {
        width: min(100%, 340px);
        border-radius: 8px;
        border: 1px solid var(--bs-border-color);
    }
    .teacher-badge-header,
    .teacher-badge-footer {
        background: var(--theme-accent);
        color: var(--text-on-accent);
    }
</style>
@endsection
