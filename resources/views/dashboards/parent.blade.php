@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4 mt-2">
    <div class="breadcrumb-title pe-3">Tableau de Bord</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Espace parent</li>
            </ol>
        </nav>
    </div>
</div>

<div class="card theme-card shadow-sm mb-4">
    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <p class="text-muted text-uppercase small fw-bold mb-1">Bienvenue</p>
            <h4 class="fw-bold mb-1">{{ $parent->nom_prenom_parent ?? auth()->user()->nomPrenom }}</h4>
            <div class="text-muted">
                <i class="bi bi-telephone me-1"></i>{{ $parent->telephone_parent ?: 'Téléphone non renseigné' }}
                @if($parent->email_parent)
                    <span class="mx-2">-</span><i class="bi bi-envelope me-1"></i>{{ $parent->email_parent }}
                @endif
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2">
                {{ session('nomEcole') ?: 'École' }}
            </span>
            @if($anneeEnCours)
                <div class="small text-muted mt-2">Année {{ $anneeEnCours->annee }}</div>
            @endif
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Mes enfants</p>
                    <h3 class="fw-bold mb-0">{{ $children->count() }}</h3>
                </div>
                <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-people fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Montant prévu</p>
                    <h3 class="fw-bold mb-0">{{ number_format($totalExpected, 0, ',', ' ') }}</h3>
                    <small class="text-muted">FCFA</small>
                </div>
                <div class="widget-icon theme-icon-box rounded-3"><i class="bi bi-receipt fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Déjà payé</p>
                    <h3 class="fw-bold mb-0 text-success">{{ number_format($totalPaid, 0, ',', ' ') }}</h3>
                    <small class="text-muted">FCFA</small>
                </div>
                <div class="widget-icon bg-success-soft text-success rounded-3"><i class="bi bi-check2-circle fs-4"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Reste à payer</p>
                    <h3 class="fw-bold mb-0 {{ $totalRemaining > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($totalRemaining, 0, ',', ' ') }}</h3>
                    <small class="text-muted">{{ $latePlans }} dossier(s) en retard</small>
                </div>
                <div class="widget-icon bg-warning-soft text-warning rounded-3"><i class="bi bi-wallet2 fs-4"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card theme-card shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Mes enfants inscrits</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th>Lien</th>
                                <th>Informer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($children as $child)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $child->prenom_eleve }} {{ $child->nom_eleve }}</div>
                                        <small class="text-muted">{{ $child->matricule ?: 'Matricule non défini' }}</small>
                                    </td>
                                    <td><span class="badge bg-light text-primary">{{ $child->classe->nom_classe ?? 'Classe non définie' }}</span></td>
                                    <td>{{ $child->pivot->lien_parent ?: 'Parent' }}</td>
                                    <td>
                                        @if($child->pivot->informer === 'Oui')
                                            <span class="badge bg-success">Oui</span>
                                        @else
                                            <span class="badge bg-secondary">Non</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Aucun élève n’est encore rattaché à ce compte parent.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Situation des paiements</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Élève</th>
                                <th>Prévu</th>
                                <th>Payé</th>
                                <th>Reste</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($financialRows as $row)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $row['eleve']->prenom_eleve ?? '' }} {{ $row['eleve']->nom_eleve ?? 'Élève' }}</div>
                                        <small class="text-muted">{{ $row['classe']->nom_classe ?? 'Classe' }}</small>
                                    </td>
                                    <td>{{ number_format($row['attendu'], 0, ',', ' ') }} F</td>
                                    <td class="text-success fw-bold">{{ number_format($row['paye'], 0, ',', ' ') }} F</td>
                                    <td class="fw-bold">{{ number_format($row['reste'], 0, ',', ' ') }} F</td>
                                    <td>
                                        @if($row['statut'] === 'À jour')
                                            <span class="badge bg-success">À jour</span>
                                        @elseif($row['statut'] === 'Retard')
                                            <span class="badge bg-danger">Retard</span>
                                        @else
                                            <span class="badge bg-warning text-dark">En cours</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Aucun plan de paiement disponible pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card theme-card shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                    <h5 class="fw-bold mb-0">Annonces de l'école</h5>
                    @if($annonces->isNotEmpty())
                        <span class="badge bg-primary">{{ $annonces->count() }}</span>
                    @endif
                </div>

                @forelse($annonces as $annonce)
                    <div class="border-bottom py-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                <i class="bi bi-megaphone"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">{{ $annonce->titre }}</div>
                                <div class="small text-muted mb-2">
                                    {{ $annonce->date_publication ? \Carbon\Carbon::parse($annonce->date_publication)->format('d/m/Y H:i') : 'Date non définie' }}
                                    @if($annonce->auteur)
                                        - {{ $annonce->auteur }}
                                    @endif
                                </div>
                                <div class="small">{{ \Illuminate\Support\Str::limit($annonce->contenu, 150) }}</div>
                                @if($annonce->fichier_joint)
                                    <div class="small text-muted mt-2">
                                        <i class="bi bi-paperclip me-1"></i>Pièce jointe disponible
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Aucune annonce publiée pour les parents pour le moment.</div>
                @endforelse
            </div>
        </div>

        <div class="card theme-card shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Derniers paiements</h5>
                @forelse($payments as $payment)
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="fw-bold">{{ $payment->eleve->prenom_eleve ?? '' }} {{ $payment->eleve->nom_eleve ?? 'Élève' }}</div>
                                <div class="small text-muted">
                                    {{ optional($payment->date_paiement)->format('d/m/Y') ?: 'Date non définie' }}
                                    @if($payment->mode_reglement)
                                        - {{ $payment->mode_reglement }}
                                    @endif
                                </div>
                            </div>
                            <div class="fw-bold text-success text-nowrap">{{ number_format((float) ($payment->montant_paye ?: $payment->montant), 0, ',', ' ') }} F</div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Aucun paiement récent.</div>
                @endforelse
            </div>
        </div>

        <div class="card theme-card shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">À retenir</h5>
                <div class="d-flex gap-3 border-bottom py-3">
                    <i class="bi bi-info-circle text-primary fs-4"></i>
                    <div>
                        <div class="fw-bold">Vos enfants rattachés</div>
                        <div class="small text-muted">Si un enfant manque, contactez l’administration pour l’ajouter à votre compte.</div>
                    </div>
                </div>
                <div class="d-flex gap-3 py-3">
                    <i class="bi bi-bell text-success fs-4"></i>
                    <div>
                        <div class="fw-bold">Contact à informer</div>
                        <div class="small text-muted">La colonne “Informer” indique si l’école utilise ce contact pour les communications.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
    .bg-success-soft { background-color: rgba(var(--bs-success-rgb), 0.1); }
    .bg-warning-soft { background-color: rgba(var(--bs-warning-rgb), 0.16); }
</style>
@endsection
