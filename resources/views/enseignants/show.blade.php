@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Enseignants</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('enseignants.index') }}">Liste des enseignants</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $enseignant->nom_prenom_enseignant }}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            <button type="button" class="btn theme-outline-btn" data-bs-toggle="modal" data-bs-target="#teacherBadgeModal"><i class="bi bi-person-vcard me-1"></i>Carte professionnelle</button>
            <a href="{{ route('enseignants.edit', $enseignant->id_enseignant) }}" class="btn theme-outline-btn"><i class="bi bi-pencil me-1"></i>Modifier</a>
            <button type="button" class="btn theme-action-btn" onclick="printTeacherProfile()"><i class="bi bi-printer me-1"></i>Imprimer Fiche</button>
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
                    <ul class="nav nav-pills teacher-tabs card-header-pills" id="teacherTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active px-4" data-bs-toggle="pill" data-bs-target="#info">Informations</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4" data-bs-toggle="pill" data-bs-target="#classes">Classes & Matières</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link px-4" data-bs-toggle="pill" data-bs-target="#bulletin">Bulletin du mois</button>
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
                        <div class="tab-pane fade" id="bulletin">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                                <div>
                                    <h5 class="fw-bold mb-1">Bulletin de salaire - {{ ucfirst($monthlyBulletin['label']) }}</h5>
                                    <div class="text-muted small">{{ $monthlyBulletin['reference'] }}</div>
                                </div>
                                @if($monthlyBulletin['managed_by_school'])
                                    <a class="btn theme-action-btn px-4" href="{{ route('enseignants.salaires.bulletin', ['id_enseignant' => $enseignant->id_enseignant, 'mois' => $monthlyBulletin['month'], 'annee' => $monthlyBulletin['year'], 'source' => $monthlyBulletin['source']]) }}">
                                        <i class="bi bi-file-earmark-pdf me-2"></i>Voir le bulletin PDF
                                    </a>
                                @else
                                    <span class="badge theme-icon-soft px-3 py-2">Salaire géré par l'État</span>
                                @endif
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3 col-6">
                                    <div class="theme-icon-soft rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-bold">À payer</small>
                                        <h5 class="mb-0 fw-bold">{{ number_format($monthlyBulletin['amount_due'], 0, ',', ' ') }} F</h5>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="theme-icon-soft rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-bold">Versé</small>
                                        <h5 class="mb-0 fw-bold text-success">{{ number_format($monthlyBulletin['paid'], 0, ',', ' ') }} F</h5>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="theme-icon-soft rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-bold">Reste</small>
                                        <h5 class="mb-0 fw-bold {{ $monthlyBulletin['remaining'] > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($monthlyBulletin['remaining'], 0, ',', ' ') }} F</h5>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="theme-icon-soft rounded-3 p-3 h-100">
                                        <small class="text-muted text-uppercase fw-bold">Statut</small>
                                        <h5 class="mb-0 fw-bold">{{ $monthlyBulletin['status'] }}</h5>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Contrat</th>
                                            <th>Base</th>
                                            <th class="text-end">Heures</th>
                                            <th class="text-end">Montant mensuel</th>
                                            <th class="text-end">Prix / heure</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold">{{ $enseignant->type_contrat_enseignant }}</td>
                                            <td>{{ $enseignant->type_contrat_enseignant === 'VCT' ? 'Émargements validés' : 'Salaire mensuel' }}</td>
                                            <td class="text-end">{{ number_format($monthlyBulletin['hours'], 2, ',', ' ') }}</td>
                                            <td class="text-end">{{ number_format($enseignant->salaire_enseignant ?? 0, 0, ',', ' ') }} F</td>
                                            <td class="text-end">{{ number_format($enseignant->prix_heure ?? 0, 0, ',', ' ') }} F</td>
                                        </tr>
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
                                <a href="{{ route('enseignants.emargements', ['id_enseignant' => $enseignant->id_enseignant]) }}" class="btn theme-action-btn px-4">
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
                                <a href="{{ route('enseignants.presences', ['id_enseignant' => $enseignant->id_enseignant]) }}" class="btn theme-action-btn px-4">
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
                <h5 class="modal-title fw-bold">Carte professionnelle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="teacher-badge mx-auto">
                    <div class="teacher-badge-flag"><span></span><span></span><span></span></div>
                    @if(!empty($enseignant->ecole?->logoEcole))
                        <img src="{{ asset($enseignant->ecole->logoEcole) }}" class="teacher-badge-logo" alt="">
                    @endif

                    <div class="teacher-badge-content">
                        <div class="teacher-badge-head">
                            <div>RÉPUBLIQUE DU MALI</div>
                            <div class="teacher-badge-motto"><span>Un Peuple</span> - <span>Un But</span> - <span>Une Foi</span></div>
                            <div class="teacher-badge-ministry">MINISTÈRE DE L'ÉDUCATION NATIONALE</div>
                            <div class="teacher-badge-admin">{{ $enseignant->ecole?->academieRef?->nom_academie ?? $enseignant->ecole?->academie ?? 'Académie non renseignée' }}</div>
                            <div class="teacher-badge-admin">{{ $enseignant->ecole?->capRef?->nom_cap ?? $enseignant->ecole?->cap ?? 'CAP non renseigné' }}</div>
                            <div class="teacher-badge-school">{{ session('nomEcole') ?: ($enseignant->ecole->nomEcole ?? 'École') }}</div>
                            <div class="teacher-badge-title">CARTE PROFESSIONNELLE</div>
                        </div>

                        <div class="teacher-badge-body">
                            <div class="teacher-badge-photo">
                                @if($enseignant->avatar_enseignant)
                                    <img src="{{ asset($enseignant->avatar_enseignant) }}" alt="">
                                @else
                                    <span>PHOTO</span>
                                @endif
                            </div>
                            <div class="teacher-badge-info">
                                <div class="teacher-badge-name">{{ $enseignant->nom_prenom_enseignant }}</div>
                                <div class="teacher-badge-line"><strong>Mat.</strong><span>{{ $enseignant->matricule ?: 'Non renseigné' }}</span></div>
                                <div class="teacher-badge-line"><strong>Contrat</strong><span>{{ $enseignant->type_contrat_enseignant ?: 'N/A' }}</span></div>
                                <div class="teacher-badge-line"><strong>Spécialité</strong><span>{{ $enseignant->specialite ?: 'Polyvalent' }}</span></div>
                                <div class="teacher-badge-line"><strong>Tél.</strong><span>{{ $enseignant->telephone_enseignant ?: 'Non renseigné' }}</span></div>
                            </div>
                        </div>

                        <div class="teacher-badge-bottom">
                            <div class="teacher-badge-mat"><strong>STATUT :</strong> {{ $enseignant->is_deleted ? 'ARCHIVÉ' : 'ACTIF' }}</div>
                            <div class="teacher-badge-sign">
                                <span>Administration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn theme-outline-btn px-4" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn theme-action-btn px-4" onclick="printTeacherBadge()">Imprimer</button>
            </div>
        </div>
    </div>
</div>

<div class="teacher-print-sheet">
    <div class="print-header">
        <div>
            <div class="print-school">{{ session('nomEcole') ?: ($enseignant->ecole->nomEcole ?? 'École') }}</div>
            <div class="print-title">Fiche enseignant</div>
        </div>
        <div class="print-date">Imprimé le {{ now()->format('d/m/Y') }}</div>
    </div>

    <div class="print-identity">
        <div class="print-photo">
            @if($enseignant->avatar_enseignant)
                <img src="{{ asset($enseignant->avatar_enseignant) }}" alt="">
            @else
                <span>{{ strtoupper(substr($enseignant->nom_prenom_enseignant, 0, 1)) }}</span>
            @endif
        </div>
        <div>
            <h1>{{ $enseignant->nom_prenom_enseignant }}</h1>
            <p>{{ $enseignant->specialite ?: 'Enseignant polyvalent' }}</p>
            <p>Matricule : <strong>{{ $enseignant->matricule ?: 'N/A' }}</strong></p>
        </div>
    </div>

    <div class="print-grid">
        <div><span>Contrat</span><strong>{{ $enseignant->type_contrat_enseignant ?: 'N/A' }}</strong></div>
        <div><span>Genre</span><strong>{{ $enseignant->genre_enseignant ?: 'N/A' }}</strong></div>
        <div><span>Date de naissance</span><strong>{{ $enseignant->date_naissance_enseignant ?: 'N/A' }}</strong></div>
        <div><span>Téléphone</span><strong>{{ $enseignant->telephone_enseignant ?: 'N/A' }}</strong></div>
        <div><span>Email</span><strong>{{ $enseignant->email_enseignant ?: 'N/A' }}</strong></div>
        <div><span>Diplôme</span><strong>{{ $enseignant->diplome_enseignant ?: 'N/A' }}</strong></div>
        <div><span>Ancienneté</span><strong>{{ $enseignant->anciennete_annees ?? 0 }} ans</strong></div>
        <div><span>Adresse / lieu</span><strong>{{ $enseignant->lieu_naissance_enseignant ?: 'N/A' }}</strong></div>
    </div>

    @if($enseignant->type_contrat_enseignant === 'FONCTIONNAIRE')
        <div class="print-section">
            <h2>Informations administratives</h2>
            <div class="print-grid">
                <div><span>Service employeur</span><strong>{{ $enseignant->service_employeur ?: 'N/A' }}</strong></div>
                <div><span>Statut matrimonial</span><strong>{{ $enseignant->statut_matrimonial ?: 'N/A' }}</strong></div>
                <div><span>Nombre d'enfants</span><strong>{{ $enseignant->nombre_enfants ?? 0 }}</strong></div>
            </div>
        </div>
    @endif

    <div class="print-section">
        <h2>Affectations pédagogiques</h2>
        <table>
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
                        <td>{{ $ligne->classe->nom_classe ?? 'N/A' }}</td>
                        <td>{{ $ligne->matiere->nom_matiere ?? 'N/A' }}</td>
                        <td>{{ number_format($ligne->coefficient, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Aucune affectation pédagogique trouvée.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    .teacher-print-sheet {
        display: none;
    }
    .teacher-badge {
        width: min(100%, 270px);
        min-height: 450px;
        position: relative;
        overflow: hidden;
        border: 2px solid var(--theme-accent);
        border-radius: 12px;
        background: #fff;
        box-shadow: inset 0 0 0 2px #f59e0b, 0 12px 30px rgba(15, 23, 42, .12);
        color: #111827;
    }
    .teacher-badge-flag {
        position: absolute;
        left: 12px;
        top: 12px;
        width: 42px;
        height: 22px;
        border: 1px solid rgba(0,0,0,.18);
        box-shadow: 0 1px 2px rgba(17,24,39,.18);
        z-index: 3;
        overflow: hidden;
    }
    .teacher-badge-flag span {
        display: block;
        float: left;
        width: 33.333%;
        height: 100%;
    }
    .teacher-badge-flag span:nth-child(1) { background: #14b53a; }
    .teacher-badge-flag span:nth-child(2) { background: #fcd116; }
    .teacher-badge-flag span:nth-child(3) { background: #ce1126; }
    .teacher-badge-logo {
        position: absolute;
        top: 10px;
        right: 12px;
        width: 40px;
        height: 36px;
        object-fit: contain;
        z-index: 3;
    }
    .teacher-badge-content {
        position: relative;
        z-index: 2;
    }
    .teacher-badge-head {
        height: 126px;
        padding: 10px 54px 6px;
        text-align: center;
        font-size: 8.7px;
        line-height: 1.16;
        overflow: hidden;
    }
    .teacher-badge-motto span:nth-child(1) { color: #15803d; }
    .teacher-badge-motto span:nth-child(2) { color: #b45309; }
    .teacher-badge-motto span:nth-child(3) { color: #b91c1c; }
    .teacher-badge-ministry {
        margin-top: 3px;
        font-size: 8.4px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .teacher-badge-admin {
        font-size: 8.2px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .teacher-badge-school {
        margin-top: 2px;
        font-size: 9px;
        font-weight: 800;
        text-transform: uppercase;
        line-height: 1.12;
    }
    .teacher-badge-title {
        margin-top: 3px;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .teacher-badge-body {
        display: block;
        padding: 4px 14px 0;
        text-align: center;
    }
    .teacher-badge-photo {
        width: 96px;
        height: 104px;
        border: 2px solid #f59e0b;
        border-radius: 12px;
        background: #f3f4f6;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin: 4px auto 7px;
        font-size: 12px;
        font-weight: 800;
    }
    .teacher-badge-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .teacher-badge-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
        text-align: left;
        font-size: 10.5px;
        line-height: 1.2;
        padding: 4px 4px 0;
    }
    .teacher-badge-name {
        margin-bottom: 7px;
        text-align: center;
        font-size: 14px;
        font-weight: 900;
        text-transform: uppercase;
        min-height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1.15;
    }
    .teacher-badge-line {
        display: block;
        padding: 3px 5px;
        border-left: 2px solid #f59e0b;
        background: #f9fafb;
    }
    .teacher-badge-line strong {
        display: block;
        color: #374151;
        font-size: 8.5px;
        line-height: 1.1;
        text-transform: uppercase;
    }
    .teacher-badge-line span {
        display: block;
        min-width: 0;
        margin-top: 1px;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.18;
    }
    .teacher-badge-bottom {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12px;
        margin: 12px 12px 10px;
        font-size: 10px;
    }
    .teacher-badge-mat {
        max-width: 62%;
    }
    .teacher-badge-sign {
        width: 90px;
        padding-top: 18px;
        border-top: 1px solid #9ca3af;
        text-align: center;
        font-size: 10px;
        color: #374151;
    }
    html[data-theme] .theme-action-btn,
    html[data-theme] .teacher-tabs .nav-link.active {
        background-color: var(--theme-accent) !important;
        border-color: var(--theme-accent) !important;
        color: #fff !important;
    }
    html[data-theme] .theme-outline-btn {
        background: rgba(255,255,255,.12) !important;
        border-color: rgba(255,255,255,.28) !important;
        color: var(--text-on-accent) !important;
    }
    html[data-theme] .card-body .theme-outline-btn,
    html[data-theme] .modal-footer .theme-outline-btn {
        background: var(--accent-light) !important;
        border-color: var(--border-color) !important;
        color: var(--theme-accent) !important;
    }
    .teacher-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }
    html[data-theme] .teacher-tabs .nav-link {
        min-height: 40px;
        background: rgba(255,255,255,.12) !important;
        border: 1px solid rgba(255,255,255,.28) !important;
        color: var(--text-on-accent) !important;
        border-radius: 8px !important;
        font-weight: 700;
    }
    @media print {
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        body * {
            visibility: hidden !important;
        }
        .teacher-print-sheet,
        .teacher-print-sheet * {
            visibility: visible !important;
        }
        .teacher-print-sheet {
            display: block !important;
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            color: #111;
            background: #fff;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .print-school {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .print-title {
            font-size: 24px;
            font-weight: 800;
            margin-top: 3px;
        }
        .print-date {
            font-size: 11px;
            color: #444;
        }
        .print-identity {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 16px;
        }
        .print-photo {
            width: 86px;
            height: 86px;
            border: 1px solid #777;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            font-size: 38px;
            font-weight: 800;
        }
        .print-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .print-identity h1 {
            font-size: 21px;
            margin: 0 0 5px;
        }
        .print-identity p {
            margin: 2px 0;
        }
        .print-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }
        .print-grid div {
            border: 1px solid #bbb;
            padding: 8px;
            min-height: 48px;
        }
        .print-grid span {
            display: block;
            color: #555;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .print-grid strong {
            font-size: 12px;
        }
        .print-section h2 {
            font-size: 15px;
            margin: 14px 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #999;
        }
        .teacher-print-sheet table {
            width: 100%;
            border-collapse: collapse;
        }
        .teacher-print-sheet th,
        .teacher-print-sheet td {
            border: 1px solid #999;
            padding: 7px;
            text-align: left;
        }
        .teacher-print-sheet th {
            background: #f1f1f1 !important;
            font-weight: 700;
        }
        body.printing-teacher-badge .teacher-print-sheet,
        body.printing-teacher-badge .teacher-print-sheet * {
            visibility: hidden !important;
        }
        body.printing-teacher-badge .teacher-badge,
        body.printing-teacher-badge .teacher-badge * {
            visibility: visible !important;
        }
        body.printing-teacher-badge .teacher-badge {
            display: block !important;
            position: absolute;
            left: 50%;
            top: 12mm;
            transform: translateX(-50%);
            width: 270px;
            min-height: 450px;
            box-shadow: inset 0 0 0 2px #f59e0b !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
@push('scripts')
    <script>
        function printTeacherProfile() {
            document.body.classList.remove('printing-teacher-badge');
            window.print();
        }

        function printTeacherBadge() {
            document.body.classList.add('printing-teacher-badge');
            window.print();
        }

        window.addEventListener('afterprint', () => {
            document.body.classList.remove('printing-teacher-badge');
        });
    </script>
@endpush
@endsection
