@extends('layouts.app')

@section('content')
@php
    $statusClass = fn ($status) => $status === 'paye' ? 'success' : ($status === 'echec' ? 'danger' : 'warning');
@endphp

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Abonnements</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Mode manuel</li>
            </ol>
        </nav>
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

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card theme-card shadow-sm h-100">
            <div class="card-header theme-header">
                <h5 class="fw-bold mb-0"><i class="bi bi-shield-check me-2"></i>État de l'abonnement</h5>
            </div>
            <div class="card-body">
                @if($abonnement)
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-muted fw-bold text-uppercase small">Statut</span>
                        <span class="badge bg-{{ $abonnement->statut === 'actif' ? 'success' : 'warning' }}">{{ ucfirst(str_replace('_', ' ', $abonnement->statut)) }}</span>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase">Formule</div>
                        <div class="fs-5 fw-bold">{{ $abonnement->offre?->nom }}</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-muted small fw-bold text-uppercase">Validité</div>
                        <div class="fw-bold">{{ $abonnement->fin_at ? $abonnement->fin_at->format('d/m/Y') : 'En attente de validation' }}</div>
                    </div>
                @else
                    <div class="text-muted">Aucun abonnement actif pour cette école.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card theme-card shadow-sm">
            <div class="card-header theme-header">
                <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-2"></i>Soumettre un paiement manuel</h5>
            </div>
            <div class="card-body">
                @if($canSubmitManual)
                <form method="POST" action="{{ route('abonnements.paiements.manual') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Formule</label>
                        <select name="offre_id" class="form-select" required>
                            <option value="">Choisir</option>
                            @foreach($offres as $offre)
                                <option value="{{ $offre->id }}" @selected(old('offre_id') == $offre->id)>
                                    {{ $offre->nom }} - {{ number_format($offre->montant, 0, ',', ' ') }} {{ $offre->devise }} / {{ $offre->duree_jours }} jours
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Canal de transfert</label>
                        <select name="mode_paiement" class="form-select" required>
                            @foreach($manualModes as $key => $label)
                                <option value="{{ $key }}" @selected(old('mode_paiement') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Référence transfert</label>
                        <input name="transaction_ref" class="form-control" value="{{ old('transaction_ref') }}" placeholder="Ex: OM-TRX-...">
                    </div>
                    <div class="col-md-6 js-receipt-wrapper">
                        <label class="form-label small fw-bold">Preuve photo</label>
                        <div class="input-group">
                            <input type="file" name="receipt" class="form-control js-receipt-input" accept="image/jpeg,image/png,image/webp" required>
                            <button type="button" class="btn btn-outline-secondary js-btn-camera" title="Prendre une photo"><i class="bi bi-camera"></i></button>
                        </div>
                        <div class="js-camera-container d-none mt-2 text-center rounded border p-2 bg-light">
                            <video class="js-camera-video w-100 rounded mb-2 bg-dark" autoplay playsinline style="max-height: 200px; object-fit: contain;"></video>
                            <div>
                                <button type="button" class="btn btn-primary btn-sm js-btn-capture"><i class="bi bi-camera-fill me-1"></i>Capturer</button>
                                <button type="button" class="btn btn-secondary btn-sm js-btn-close-camera"><i class="bi bi-x-circle me-1"></i>Annuler</button>
                            </div>
                        </div>
                        <div class="js-receipt-preview d-none mt-2 text-center position-relative d-inline-block">
                            <img src="" class="img-fluid rounded border js-preview-img" style="max-height: 150px;">
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 js-btn-remove-receipt" title="Supprimer l'image"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Note</label>
                        <textarea name="owner_note" rows="2" class="form-control" placeholder="Précision utile pour le superadmin">{{ old('owner_note') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button class="btn theme-action-btn" type="submit">
                            <i class="bi bi-send-check me-1"></i>Envoyer la demande
                        </button>
                    </div>
                </form>
                @else
                    <div class="alert alert-warning mb-0">Sélectionnez une école avant de soumettre une demande d'abonnement.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card theme-card shadow-sm mt-4">
    <div class="card-header theme-header">
        <h5 class="fw-bold mb-0">Historique de mes demandes</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Formule</th>
                    <th>Canal</th>
                    <th class="text-end">Montant</th>
                    <th>Statut</th>
                    <th>Preuve</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            @forelse($paiements as $paiement)
                <tr>
                    <td><a href="{{ route('abonnements.paiements.show', $paiement->reference) }}" class="fw-bold">{{ $paiement->reference }}</a></td>
                    <td>{{ $paiement->offre?->nom }}</td>
                    <td>{{ $paiement->fournisseur }}</td>
                    <td class="text-end fw-bold">{{ number_format($paiement->montant, 0, ',', ' ') }} {{ $paiement->devise }}</td>
                    <td><span class="badge bg-{{ $statusClass($paiement->statut) }}">{{ \App\Services\Abonnements\AbonnementPaymentService::statusLabel($paiement->statut) }}</span></td>
                    <td>
                        @if($paiement->preuve_url)
                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-proof" data-proof-url="{{ asset(ltrim($paiement->preuve_url, '/')) }}" data-proof-title="Preuve {{ $paiement->reference }}" data-bs-toggle="modal" data-bs-target="#proofPreviewModal">Voir</button>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $paiement->created_at?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Aucune demande d'abonnement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($canReview)
<div class="card theme-card shadow-sm mt-4">
    <div class="card-header theme-header d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0"><i class="bi bi-check2-square me-2"></i>Demandes à valider</h5>
        <form method="GET" action="{{ route('abonnements.index') }}" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Tous</option>
                <option value="en_attente" @selected(request('status') === 'en_attente')>En attente</option>
                <option value="paye" @selected(request('status') === 'paye')>Payé</option>
                <option value="echec" @selected(request('status') === 'echec')>Échoué</option>
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th>École</th>
                    <th>Référence</th>
                    <th>Plan</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Preuve</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse($adminPaiements as $paiement)
                <tr>
                    <td>{{ $paiement->ecole?->nomEcole ?? '-' }}</td>
                    <td class="fw-bold">{{ $paiement->reference }}</td>
                    <td>{{ $paiement->offre?->nom }}</td>
                    <td>{{ number_format($paiement->montant, 0, ',', ' ') }} {{ $paiement->devise }}</td>
                    <td><span class="badge bg-{{ $statusClass($paiement->statut) }}">{{ \App\Services\Abonnements\AbonnementPaymentService::statusLabel($paiement->statut) }}</span></td>
                    <td>
                        @if($paiement->preuve_url)
                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-proof" data-proof-url="{{ asset(ltrim($paiement->preuve_url, '/')) }}" data-proof-title="Preuve {{ $paiement->ecole?->nomEcole ?? '' }}" data-bs-toggle="modal" data-bs-target="#proofPreviewModal">Voir</button>
                        @else
                            -
                        @endif
                    </td>
                    <td style="min-width: 230px;">
                        @if($paiement->statut === 'en_attente')
                            <form method="POST" action="{{ route('abonnements.paiements.approve', $paiement) }}" class="mb-2">
                                @csrf
                                <input name="review_note" class="form-control form-control-sm mb-1" placeholder="Note optionnelle">
                                <button class="btn btn-sm btn-success w-100" type="submit">Valider</button>
                            </form>
                            <form method="POST" action="{{ route('abonnements.paiements.reject', $paiement) }}">
                                @csrf
                                <input name="review_note" class="form-control form-control-sm mb-1" placeholder="Motif optionnel">
                                <button class="btn btn-sm btn-danger w-100" type="submit">Rejeter</button>
                            </form>
                        @else
                            <span class="text-muted small">{{ $paiement->reviewed_at?->format('d/m/Y H:i') ?? 'Aucune action' }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">Aucune demande trouvée.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@if($canConfigure)
<div class="card theme-card shadow-sm mt-4">
    <div class="card-header theme-header d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0"><i class="bi bi-sliders me-2"></i>Tarification abonnement</h5>
        <span class="badge bg-primary">Superadmin</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('abonnements.offres.store') }}" class="row g-3 align-items-end mb-4">
            @csrf
            <div class="col-md-2"><label class="form-label small fw-bold">Code</label><input name="code" class="form-control" placeholder="mensuel" required></div>
            <div class="col-md-3"><label class="form-label small fw-bold">Libellé</label><input name="nom" class="form-control" placeholder="Abonnement mensuel" required></div>
            <div class="col-md-2"><label class="form-label small fw-bold">Prix</label><input name="montant" type="number" min="1" step="1" class="form-control" required></div>
            <div class="col-md-1"><label class="form-label small fw-bold">Devise</label><input name="devise" class="form-control" value="XOF" required></div>
            <div class="col-md-2"><label class="form-label small fw-bold">Durée (jours)</label><input name="duree_jours" type="number" min="1" class="form-control" value="30" required></div>
            <div class="col-md-1"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="actif" value="1" id="new-offre-active" checked><label class="form-check-label small fw-bold" for="new-offre-active">Actif</label></div></div>
            <div class="col-md-1"><button class="btn theme-action-btn w-100" type="submit"><i class="bi bi-plus-lg"></i></button></div>
            <div class="col-12"><label class="form-label small fw-bold">Description</label><input name="description" class="form-control" placeholder="Accès complet à KalanNet pendant la durée choisie."></div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Libellé</th>
                        <th class="text-end">Prix</th>
                        <th>Devise</th>
                        <th>Durée</th>
                        <th>Actif</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($allOffres as $offre)
                    <tr>
                        <td colspan="7">
                            <form method="POST" action="{{ route('abonnements.offres.update', $offre) }}" class="row g-2 align-items-center">
                                @csrf
                                @method('PUT')
                                <div class="col-md-1"><input name="code" class="form-control form-control-sm" value="{{ $offre->code }}" required></div>
                                <div class="col-md-3">
                                    <input name="nom" class="form-control form-control-sm mb-1" value="{{ $offre->nom }}" required>
                                    <input name="description" class="form-control form-control-sm" value="{{ $offre->description }}" placeholder="Description">
                                </div>
                                <div class="col-md-2"><input name="montant" type="number" min="1" step="1" class="form-control form-control-sm text-end" value="{{ (int) $offre->montant }}" required></div>
                                <div class="col-md-1"><input name="devise" class="form-control form-control-sm" value="{{ $offre->devise }}" required></div>
                                <div class="col-md-2"><input name="duree_jours" type="number" min="1" class="form-control form-control-sm" value="{{ $offre->duree_jours }}" required></div>
                                <div class="col-md-1">
                                    <select name="actif" class="form-select form-select-sm">
                                        <option value="1" @selected($offre->actif)>Oui</option>
                                        <option value="0" @selected(!$offre->actif)>Non</option>
                                    </select>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button class="btn btn-sm btn-primary" type="submit">Mettre à jour</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if(session('open_subscription_renewal_modal') && $canSubmitManual)
<div class="modal fade" id="renewalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Renouvellement abonnement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="POST" action="{{ route('abonnements.paiements.manual') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning border-0 border-start border-warning border-4">
                        Votre abonnement est expiré. Envoyez une demande de renouvellement pour restaurer l’accès complet.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Formule</label>
                            <select name="offre_id" class="form-select" required>
                                <option value="">Choisir</option>
                                @foreach($offres as $offre)
                                    <option value="{{ $offre->id }}">
                                        {{ $offre->nom }} - {{ number_format($offre->montant, 0, ',', ' ') }} {{ $offre->devise }} / {{ $offre->duree_jours }} jours
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Canal de transfert</label>
                            <select name="mode_paiement" class="form-select" required>
                                @foreach($manualModes as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Référence transfert</label>
                            <input name="transaction_ref" class="form-control" placeholder="Ex: OM-TRX-...">
                        </div>
                        <div class="col-md-6 js-receipt-wrapper">
                            <label class="form-label small fw-bold">Preuve photo</label>
                            <div class="input-group">
                                <input type="file" name="receipt" class="form-control js-receipt-input" accept="image/jpeg,image/png,image/webp" required>
                                <button type="button" class="btn btn-outline-secondary js-btn-camera" title="Prendre une photo"><i class="bi bi-camera"></i></button>
                            </div>
                            <div class="js-camera-container d-none mt-2 text-center rounded border p-2 bg-light">
                                <video class="js-camera-video w-100 rounded mb-2 bg-dark" autoplay playsinline style="max-height: 200px; object-fit: contain;"></video>
                                <div>
                                    <button type="button" class="btn btn-primary btn-sm js-btn-capture"><i class="bi bi-camera-fill me-1"></i>Capturer</button>
                                    <button type="button" class="btn btn-secondary btn-sm js-btn-close-camera"><i class="bi bi-x-circle me-1"></i>Annuler</button>
                                </div>
                            </div>
                            <div class="js-receipt-preview d-none mt-2 text-center position-relative d-inline-block">
                                <img src="" class="img-fluid rounded border js-preview-img" style="max-height: 150px;">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 js-btn-remove-receipt" title="Supprimer l'image"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Note</label>
                            <textarea name="owner_note" rows="2" class="form-control" placeholder="Précision utile pour le superadmin"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Plus tard</button>
                    <button class="btn theme-action-btn" type="submit">
                        <i class="bi bi-send-check me-1"></i>Envoyer la demande
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="proofPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proofPreviewTitle">Preuve d'abonnement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="proofPreviewImg" src="" alt="Preuve abonnement" class="img-fluid rounded border" style="max-height:70vh;">
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const renewalModal = document.getElementById('renewalModal');
    if (renewalModal && window.bootstrap) {
        new bootstrap.Modal(renewalModal).show();
    }

    document.querySelectorAll('.btn-view-proof').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('proofPreviewImg').src = this.dataset.proofUrl || '';
            document.getElementById('proofPreviewTitle').textContent = this.dataset.proofTitle || 'Preuve d\'abonnement';
        });
    });

    // Gestion de la caméra et de l'aperçu pour les preuves photo
    document.querySelectorAll('.js-receipt-wrapper').forEach(wrapper => {
        const fileInput = wrapper.querySelector('.js-receipt-input');
        const btnCamera = wrapper.querySelector('.js-btn-camera');
        const cameraContainer = wrapper.querySelector('.js-camera-container');
        const video = wrapper.querySelector('.js-camera-video');
        const btnCapture = wrapper.querySelector('.js-btn-capture');
        const btnCloseCamera = wrapper.querySelector('.js-btn-close-camera');
        
        const previewContainer = wrapper.querySelector('.js-receipt-preview');
        const previewImg = wrapper.querySelector('.js-preview-img');
        const btnRemoveReceipt = wrapper.querySelector('.js-btn-remove-receipt');

        let stream = null;

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                }
                reader.readAsDataURL(this.files[0]);
            } else {
                previewContainer.classList.add('d-none');
            }
        });

        btnCamera.addEventListener('click', async function() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video.srcObject = stream;
                cameraContainer.classList.remove('d-none');
                btnCamera.disabled = true;
                fileInput.disabled = true;
            } catch (err) {
                alert('Impossible d\'accéder à la caméra : ' + err.message);
            }
        });

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.srcObject = null;
            cameraContainer.classList.add('d-none');
            btnCamera.disabled = false;
            fileInput.disabled = false;
        }

        btnCloseCamera.addEventListener('click', stopCamera);

        btnCapture.addEventListener('click', function() {
            if (!stream) return;
            
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            canvas.toBlob(function(blob) {
                const file = new File([blob], "capture_camera.jpg", { type: "image/jpeg" });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                
                fileInput.dispatchEvent(new Event('change'));
                stopCamera();
            }, 'image/jpeg', 0.8);
        });

        btnRemoveReceipt.addEventListener('click', function() {
            fileInput.value = '';
            previewContainer.classList.add('d-none');
            previewImg.src = '';
        });
    });
});
</script>
@endpush
@endsection
