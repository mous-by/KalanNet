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

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Progression du cahier de présence</h5>
            </div>
            <div class="card-body p-4">
                @php($presenceByChild = $childrenPresenceProgressRows->groupBy(fn ($row) => $row['child']->id_eleve))
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th>Leçon</th>
                                <th>Enseignant</th>
                                <th>Date</th>
                                <th style="min-width: 240px;">Progression</th>
                                <th class="text-end">Durée</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($presenceByChild as $childRows)
                                @foreach($childRows as $index => $row)
                                    <tr>
                                        @if($index === 0)
                                            <td class="fw-bold text-center align-middle" rowspan="{{ $childRows->count() }}">
                                                {{ $row['child']->prenom_eleve }} {{ $row['child']->nom_eleve }}
                                            </td>
                                            <td class="text-center align-middle" rowspan="{{ $childRows->count() }}">
                                                {{ $row['classe']->nom_classe ?? 'Classe non définie' }}
                                            </td>
                                        @endif
                                        <td>{{ $row['titre'] }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $row['teacher'] }}</div>
                                            <small class="text-muted">{{ $row['teacher_phone'] ?: 'Téléphone non renseigné' }}</small>
                                        </td>
                                        <td>{{ optional($row['date'])->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $row['percent'] }}%"></div>
                                                </div>
                                                <span class="fw-bold text-primary text-nowrap">{{ number_format($row['percent'], 0, ',', ' ') }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-end">{{ number_format($row['hours'], 2, ',', ' ') }} h</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Aucune progression de présence validée pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
                                <th class="text-end">Dossier</th>
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
                                    <td class="text-end">
                                        <a href="{{ route('eleves.show', $child->id_eleve) }}" class="btn btn-sm btn-primary">
                                            <i class="bi bi-folder2-open me-1"></i>Ouvrir
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Aucun élève n’est encore rattaché à ce compte parent.</td>
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
                <h5 class="fw-bold mb-3">Bulletins publiés</h5>
                @forelse($publishedBulletins as $bulletin)
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-bold">{{ $bulletin['child']->prenom_eleve }} {{ $bulletin['child']->nom_eleve }}</div>
                                <div class="small text-muted">
                                    {{ $bulletin['periode'] }}
                                    @if($bulletin['published_at'])
                                        - publié le {{ \Carbon\Carbon::parse($bulletin['published_at'])->format('d/m/Y') }}
                                    @endif
                                </div>
                            </div>
                            <a href="{{ $bulletin['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf me-1"></i>Ouvrir
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Aucun bulletin publié pour le moment.</div>
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

        <div class="card theme-card shadow-sm" id="appel-presence-parent">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                    <h5 class="fw-bold mb-0">Appel de présence</h5>
                    <form method="GET" action="{{ route('dashboard') }}#appel-presence-parent" data-auto-filter="true">
                        <select name="appel_presence_periode" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="today" @selected(($callPeriod ?? 'today') === 'today')>Aujourd'hui</option>
                            <option value="7days" @selected(($callPeriod ?? 'today') === '7days')>7 jours</option>
                            <option value="30days" @selected(($callPeriod ?? 'today') === '30days')>30 jours</option>
                            <option value="all" @selected(($callPeriod ?? 'today') === 'all')>Tout</option>
                        </select>
                    </form>
                </div>
                @forelse($childrenCallRows as $row)
                    <div class="border-bottom py-3">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px;">
                                <i class="bi bi-clipboard-check"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">{{ $row['child']->prenom_eleve ?? '' }} {{ $row['child']->nom_eleve ?? 'Élève' }}</div>
                                <div class="small text-muted">
                                    {{ $row['classe']->nom_classe ?? 'Classe non définie' }}
                                    @if($row['date'])
                                        - {{ optional($row['date'])->format('d/m/Y') }}
                                    @endif
                                </div>
                                <div class="small mt-1">
                                    {{ $row['libelle'] }}
                                    @if($row['matiere'])
                                        - {{ $row['matiere']->nom_matiere }}
                                    @endif
                                </div>
                                <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-warning text-dark">{{ $row['statut']->type_controle ?? 'Statut non renseigné' }}</span>
                                    @if($row['heure_debut'] && $row['heure_fin'])
                                        <span class="small text-muted">{{ substr($row['heure_debut'], 0, 5) }} - {{ substr($row['heure_fin'], 0, 5) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">
                        Aucun appel de présence disponible pour {{ ($callPeriod ?? 'today') === 'today' ? 'aujourd’hui' : 'cette période' }}.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1 mb-4">
    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-header bg-transparent border-0 p-4 pb-0">
                <h5 class="fw-bold mb-0">Progression pédagogique par matière</h5>
            </div>
            <div class="card-body p-4">
                @php($progressByChild = $childrenProgressRows->groupBy(fn ($row) => $row['child']->id_eleve))
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Élève</th>
                                <th>Classe</th>
                                <th class="text-center">Matière</th>
                                <th>Enseignant</th>
                                <th style="min-width: 240px;">Progression</th>
                                <th class="text-center">Leçons</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($progressByChild as $childRows)
                                @foreach($childRows as $index => $row)
                                    <tr>
                                        @if($index === 0)
                                            <td class="fw-bold text-center align-middle" rowspan="{{ $childRows->count() }}">
                                                {{ $row['child']->prenom_eleve }} {{ $row['child']->nom_eleve }}
                                            </td>
                                            <td class="text-center align-middle" rowspan="{{ $childRows->count() }}">
                                                {{ $row['classe']->nom_classe ?? 'Classe non définie' }}
                                            </td>
                                        @endif
                                        <td class="text-center fw-semibold">{{ $row['matiere'] }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $row['teacher'] }}</div>
                                            <small class="text-muted">{{ $row['teacher_phone'] ?: 'Téléphone non renseigné' }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $row['percent'] }}%"></div>
                                                </div>
                                                <span class="fw-bold text-primary text-nowrap">{{ $row['percent'] }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $row['completed'] }}/{{ $row['total'] }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Aucune progression pédagogique disponible pour le moment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
