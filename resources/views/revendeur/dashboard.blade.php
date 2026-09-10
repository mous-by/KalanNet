@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ $revendeur->nom }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item active" aria-current="page">Mes écoles</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('revendeur.tarifs') }}" class="btn theme-action-btn"><i class="bi bi-tags-fill me-1"></i>Mes tarifs de revente</a>
    </div>

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2"></i>Mes numéros de dépôt</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Les écoles que vous apportez verront ces numéros (au lieu des numéros par défaut de KalanNet) pour vous régler directement.</p>
            <form method="POST" action="{{ route('revendeur.numeros.update') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-md-5">
                    <label class="form-label small fw-bold">Numéro Orange Money / Wave</label>
                    <input name="numero_orange_wave" class="form-control" value="{{ old('numero_orange_wave', $revendeur->numero_orange_wave) }}" placeholder="Ex: 74745669">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold">Numéro MobiCash</label>
                    <input name="numero_mobicash" class="form-control" value="{{ old('numero_mobicash', $revendeur->numero_mobicash) }}" placeholder="Ex: 67205736">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn theme-action-btn w-100" type="submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    @forelse($rows as $row)
        @php($ecole = $row['ecole'])
        @php($abonnement = $row['abonnement'])
        <div class="card theme-card shadow-sm mb-4">
            <div class="card-header theme-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold">{{ $ecole->nomEcole }}</h5>
                @if($abonnement)
                    @php($estActif = $abonnement->statut === 'actif' && (!$abonnement->fin_at || $abonnement->fin_at->isFuture()))
                    <span class="badge {{ $estActif ? 'bg-success' : 'bg-warning text-dark' }}">
                        {{ $estActif ? 'Abonnement actif' : 'Abonnement expiré / en attente' }}
                    </span>
                @else
                    <span class="badge bg-secondary">Aucun abonnement</span>
                @endif
            </div>
            <div class="card-body">
                @if($abonnement)
                    <p class="mb-3 small text-muted">
                        Formule : <b>{{ $abonnement->offre->nom ?? '—' }}</b>
                        @if($abonnement->debut_at) — Depuis le {{ $abonnement->debut_at->format('d/m/Y') }} @endif
                        @if($abonnement->fin_at) — Jusqu'au {{ $abonnement->fin_at->format('d/m/Y') }} @else — Sans date de fin @endif
                    </p>
                @endif

                <h6 class="fw-bold mb-2">Historique de paiement</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Formule</th>
                                <th class="text-end">Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($row['paiements'] as $paiement)
                                <tr>
                                    <td>{{ optional($paiement->paye_at ?? $paiement->created_at)->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $paiement->offre->nom ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($paiement->montant, 0, ',', ' ') }} {{ $paiement->devise }}</td>
                                    <td>
                                        <span class="badge {{ $paiement->statut === 'paye' ? 'bg-success' : ($paiement->statut === 'en_attente' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                            {{ \App\Services\Abonnements\AbonnementPaymentService::statusLabel($paiement->statut) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">Aucun paiement enregistré.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card theme-card shadow-sm">
            <div class="card-body text-center text-muted py-4">Aucune école ne vous est encore rattachée.</div>
        </div>
    @endforelse

    @if($pendingValidations->isNotEmpty())
    <div class="modal fade" id="pendingValidationsModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-exclamation-triangle-fill me-2"></i>Paiements de vos écoles en attente de validation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Vous avez <strong>{{ $pendingValidations->count() }}</strong> demande(s) d'abonnement en attente pour les écoles que vous avez apportées. Validez-les pour leur redonner accès à KalanNet.</p>
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
                        <div class="text-center mt-2 small text-muted">Et {{ $pendingValidations->count() - 5 }} autre(s) demande(s)...</div>
                    @endif
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

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
@endsection
