@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Abonnement</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item active" aria-current="page">Réabonnement requis</li>
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

<div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3">
    <i class="bi bi-exclamation-octagon-fill fs-3"></i>
    <div>
        <div class="fw-bold">Votre abonnement KalanNet a expiré.</div>
        <div class="small">L'accès aux autres modules est suspendu tant que le renouvellement n'est pas validé. Suivez les étapes ci-dessous pour vous réabonner.</div>
    </div>
</div>

<div class="card theme-card shadow-sm mb-4">
    <div id="reabonnementCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="6000">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#reabonnementCarousel" data-bs-slide-to="0" class="active" aria-current="true"></button>
            <button type="button" data-bs-target="#reabonnementCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#reabonnementCarousel" data-bs-slide-to="2"></button>
            <button type="button" data-bs-target="#reabonnementCarousel" data-bs-slide-to="3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <div class="p-5 text-center">
                    <div class="display-4 mb-3"><i class="bi bi-1-circle-fill text-primary"></i></div>
                    <h5 class="fw-bold">1. Choisissez votre formule</h5>
                    <p class="text-muted mb-0">Sélectionnez ci-dessous la formule d'abonnement correspondant à votre école, dans le formulaire "Soumettre mon paiement".</p>
                </div>
            </div>
            <div class="carousel-item">
                <div class="p-5 text-center">
                    <div class="display-4 mb-3"><i class="bi bi-2-circle-fill text-primary"></i></div>
                    <h5 class="fw-bold">2. Effectuez le transfert</h5>
                    <p class="text-muted mb-2">Envoyez le montant de la formule vers l'un de ces numéros, puis conservez le message de confirmation :</p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <div class="border rounded-3 px-3 py-2 bg-light">
                            <div class="small text-muted">Orange Money / Wave</div>
                            <div class="fw-bold fs-5">{{ implode(' ', str_split($manualNumbers['orange_wave'], 2)) }}</div>
                        </div>
                        <div class="border rounded-3 px-3 py-2 bg-light">
                            <div class="small text-muted">MobiCash</div>
                            <div class="fw-bold fs-5">{{ implode(' ', str_split($manualNumbers['mobicash'], 2)) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="p-5 text-center">
                    <div class="display-4 mb-3"><i class="bi bi-3-circle-fill text-primary"></i></div>
                    <h5 class="fw-bold">3. Envoyez la preuve</h5>
                    <p class="text-muted mb-2">Prenez une capture d'écran du message de confirmation reçu après le transfert, et indiquez la référence (ID) dans le formulaire ci-dessous.</p>
                    <div class="d-inline-block text-start border rounded-3 p-3 bg-light mx-auto" style="max-width: 360px;">
                        <div class="small text-muted mb-1"><i class="bi bi-chat-square-text me-1"></i>Exemple de message reçu (seul le montant et l'ID seront différents pour vous) :</div>
                        <div class="fst-italic small">
                            « Votre transfert de 2 070 FCFA vers le {{ $manualNumbers['orange_wave'] }} a réussi. Frais : 20 FCFA. ID : PP260906.2033.A62527. » — OFM MALI
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="p-5 text-center">
                    <div class="display-4 mb-3"><i class="bi bi-4-circle-fill text-primary"></i></div>
                    <h5 class="fw-bold">4. Attendez la validation</h5>
                    <p class="text-muted mb-0">Votre demande passe "En attente". Dès qu'elle est validée, l'accès complet à KalanNet est rétabli automatiquement — aucune autre action de votre part.</p>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#reabonnementCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#reabonnementCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
    </div>
</div>

<div class="card theme-card shadow-sm">
    <div class="card-header theme-header">
        <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-2"></i>Soumettre mon paiement</h5>
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
                            {{ $offre->nom }} - {{ number_format($offre->montant_effectif ?? $offre->montant, 0, ',', ' ') }} {{ $offre->devise }} / {{ $offre->duree_jours }} jours
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
@endsection

@push('scripts')
@include('abonnements._receipt-capture-script')
@endpush
