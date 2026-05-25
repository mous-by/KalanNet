@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4 mt-2">
    <div class="breadcrumb-title pe-3">Superadmin</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Contrôle Global</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
@endif

<!-- Santé de l'Application -->
<h5 class="fw-bold mb-3 mt-4"><i class="bi bi-heart-pulse text-danger me-2"></i>Santé de l'Application</h5>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100 {{ $health['app_debug'] ? 'border-danger' : '' }}">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Mode Debug</p>
                    <h3 class="fw-bold mb-0 {{ $health['app_debug'] ? 'text-danger' : 'text-success' }}">
                        {{ $health['app_debug'] ? 'ACTIVÉ' : 'DÉSACTIVÉ' }}
                    </h3>
                    <small class="{{ $health['app_debug'] ? 'text-danger' : 'text-muted' }} d-block mt-2">
                        @if($health['app_debug'])
                            <i class="bi bi-exclamation-triangle"></i> Faille de sécurité potentielle
                        @else
                            <i class="bi bi-shield-check"></i> Sécurisé
                        @endif
                    </small>
                </div>
                <div class="widget-icon rounded-3 {{ $health['app_debug'] ? 'bg-danger-soft text-danger' : 'theme-icon-box' }}">
                    <i class="bi bi-bug fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Environnement</p>
                    <h3 class="fw-bold mb-0">{{ strtoupper($health['app_env']) }}</h3>
                    <small class="text-muted d-block mt-2">PHP {{ $health['php_version'] }}</small>
                </div>
                <div class="widget-icon theme-icon-box rounded-3">
                    <i class="bi bi-server fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100 {{ $health['db_connection'] !== 'OK' ? 'border-danger' : '' }}">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Base de Données</p>
                    <h3 class="fw-bold mb-0 {{ $health['db_connection'] === 'OK' ? 'text-success' : 'text-danger' }}">
                        {{ $health['db_connection'] }}
                    </h3>
                    <small class="text-muted d-block mt-2">Connexion active</small>
                </div>
                <div class="widget-icon theme-icon-box rounded-3">
                    <i class="bi bi-database fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-body p-4 d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted text-uppercase small fw-bold mb-1">Espace Disque</p>
                    <h3 class="fw-bold mb-0">
                        @if($health['disk_free_space'] !== 'N/A' && $health['disk_total_space'] !== 'N/A')
                            {{ $health['disk_free_space'] }} / {{ $health['disk_total_space'] }} Go
                        @else
                            Non disponible
                        @endif
                    </h3>
                    <small class="text-muted d-block mt-2">
                        @if($health['disk_free_space'] !== 'N/A' && $health['disk_total_space'] !== 'N/A')
                            {{ round((($health['disk_total_space'] - $health['disk_free_space']) / $health['disk_total_space']) * 100) }}% utilisé
                        @endif
                    </small>
                </div>
                <div class="widget-icon theme-icon-box rounded-3">
                    <i class="bi bi-hdd fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Ecoles -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 bg-card mb-4">
            <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2"></i>Vue Globale des Écoles</h5>
                <a href="{{ route('configuration.ecoles') }}" class="btn btn-sm btn-outline-primary">Gérer Écoles</a>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>École</th>
                                <th>Formule</th>
                                <th>Statut</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Reste</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($subscriptionOverview as $row)
                            @php
                                $abonnementRow = $row['abonnement'];
                                $remaining = $row['days_remaining'];
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $row['ecole']->nomEcole }}</td>
                                <td>{{ $abonnementRow?->offre?->nom ?? 'Non défini' }}</td>
                                <td>
                                    @if($abonnementRow)
                                        <span class="badge bg-{{ $abonnementRow->statut === 'actif' ? 'success' : 'warning' }}">{{ ucfirst($abonnementRow->statut) }}</span>
                                    @else
                                        <span class="badge bg-secondary">Aucun</span>
                                    @endif
                                </td>
                                @if($abonnementRow)
                                    @php
                                        $subscriptionFormId = 'subscription-dates-'.$abonnementRow->id;
                                    @endphp
                                    <td style="min-width: 130px;">
                                        <input form="{{ $subscriptionFormId }}" type="date" name="debut_at" class="form-control form-control-sm" value="{{ $abonnementRow->debut_at?->format('Y-m-d') }}" required>
                                    </td>
                                    <td style="min-width: 130px;">
                                        <input form="{{ $subscriptionFormId }}" type="date" name="fin_at" class="form-control form-control-sm" value="{{ $abonnementRow->fin_at?->format('Y-m-d') }}" required>
                                    </td>
                                    <td>
                                        @if($remaining !== null)
                                            <span class="badge bg-{{ $remaining < 0 ? 'danger' : ($remaining <= 7 ? 'warning' : 'success') }}">
                                                {{ $remaining < 0 ? 'Expiré' : $remaining.' j' }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <form id="{{ $subscriptionFormId }}" method="POST" action="{{ route('dashboard.abonnements.dates', $abonnementRow) }}">
                                            @csrf
                                            @method('PUT')
                                            <button class="btn btn-sm btn-primary" type="submit">Sauver</button>
                                        </form>
                                    </td>
                                @else
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td class="text-end"><a href="{{ route('configuration.ecoles') }}" class="btn btn-sm btn-outline-primary">Attribuer</a></td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucune école trouvée.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Utilisateurs Connectés -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 bg-card mb-4 h-100">
            <div class="card-header bg-transparent border-0 p-4 pb-0 d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>En Ligne (15 min)</h5>
                <span class="badge bg-success rounded-pill">{{ $connectedUsers->count() }}</span>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small">
                        <tbody>
                            @forelse($connectedUsers as $u)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary-soft text-primary d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                                                {{ strtoupper(substr($u->nomPrenom, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold">{{ $u->nomPrenom }}</div>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    {{ $u->droit }} 
                                                    @if($u->ecole) - <span class="text-primary">{{ $u->ecole->nomEcole }}</span> @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="text-muted" style="font-size: 10px;">Connecté à:</div>
                                        <div class="fw-bold">{{ $u->last_login_at ? $u->last_login_at->format('H:i') : 'N/A' }}</div>
                                        <div class="text-success" style="font-size: 10px;">{{ $u->last_activity ? $u->last_activity->diffForHumans(null, true, true) : 'Maintenant' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-3">Aucun utilisateur connecté récemment.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($pendingValidations->isNotEmpty())
<div class="modal fade" id="pendingValidationsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill me-2"></i>Abonnements en attente de validation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Vous avez <strong>{{ $pendingValidations->count() }}</strong> demande(s) d'abonnement en attente. Veuillez les traiter pour débloquer l'accès aux écoles concernées.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>École</th>
                                <th>Formule</th>
                                <th>Montant</th>
                                <th>Preuve</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingValidations->take(5) as $pending)
                            <tr>
                                <td class="fw-bold">{{ $pending->ecole?->nomEcole ?? 'Inconnue' }}</td>
                                <td>
                                    {{ $pending->offre?->nom }}<br>
                                    <small class="text-muted">{{ $pending->reference }}</small>
                                </td>
                                <td>{{ number_format($pending->montant, 0, ',', ' ') }} {{ $pending->devise }}</td>
                                <td>
                                    @if($pending->preuve_url)
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-view-proof" data-proof-url="{{ asset(ltrim($pending->preuve_url, '/')) }}" data-proof-title="Preuve {{ $pending->ecole?->nomEcole ?? '' }}" data-bs-toggle="modal" data-bs-target="#proofPreviewModal">Voir</button>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="min-width: 200px;">
                                    <form method="POST" action="{{ route('abonnements.paiements.approve', $pending) }}" class="d-inline-block w-100 mb-1">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input name="review_note" class="form-control" placeholder="Note (opt)">
                                            <button class="btn btn-success" type="submit" title="Valider"><i class="bi bi-check-lg" style="color: white !important; font-weight: bold;"></i></button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('abonnements.paiements.reject', $pending) }}" class="d-inline-block w-100">
                                        @csrf
                                        <div class="input-group input-group-sm">
                                            <input name="review_note" class="form-control" placeholder="Motif (opt)">
                                            <button class="btn btn-danger" type="submit" title="Rejeter"><i class="bi bi-x-lg" style="color: white !important; font-weight: bold;"></i></button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($pendingValidations->count() > 5)
                    <div class="text-center mt-2 small text-muted">Et {{ $pendingValidations->count() - 5 }} autre(s) demande(s)... <a href="{{ route('abonnements.index', ['status' => 'en_attente']) }}">Voir tout</a></div>
                @endif
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de prévisualisation de la preuve -->
<div class="modal fade" id="proofPreviewModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proofPreviewTitle">Preuve d'abonnement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" data-bs-toggle="modal" data-bs-target="#pendingValidationsModal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="proofPreviewImg" src="" alt="Preuve abonnement" class="img-fluid rounded border" style="max-height:70vh;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#pendingValidationsModal">Retour aux validations</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pendingModal = document.getElementById('pendingValidationsModal');
        if (pendingModal && window.bootstrap) {
            new bootstrap.Modal(pendingModal).show();
        }

        document.querySelectorAll('.btn-view-proof').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('proofPreviewImg').src = this.dataset.proofUrl || '';
                document.getElementById('proofPreviewTitle').textContent = this.dataset.proofTitle || 'Preuve d\'abonnement';
            });
        });
    });
</script>
@endif

<style>
    .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
    .bg-primary-soft { background-color: rgba(var(--bs-primary-rgb), 0.1); }
    .bg-success-soft { background-color: rgba(var(--bs-success-rgb), 0.1); }
    .bg-danger-soft { background-color: rgba(var(--bs-danger-rgb), 0.1); }
</style>
@endsection
