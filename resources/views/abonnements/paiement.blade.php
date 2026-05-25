@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Abonnements</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('abonnements.index') }}">Aperçu</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $paiement->reference }}</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
@endif

<div class="card theme-card shadow-sm">
    <div class="card-header theme-header d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0">Suivi du paiement</h5>
        <span class="badge bg-{{ $paiement->statut === 'paye' ? 'success' : 'warning' }}">{{ ucfirst(str_replace('_', ' ', $paiement->statut)) }}</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small fw-bold text-uppercase">Référence</div>
                <div class="fw-bold font-monospace">{{ $paiement->reference }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-bold text-uppercase">Formule</div>
                <div class="fw-bold">{{ $paiement->offre?->nom }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-bold text-uppercase">Montant</div>
                <div class="fw-bold">{{ number_format($paiement->montant, 0, ',', ' ') }} {{ $paiement->devise }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-bold text-uppercase">Canal</div>
                <div class="fw-bold">{{ \App\Services\Abonnements\AbonnementPaymentService::PROVIDERS[$paiement->fournisseur] ?? $paiement->fournisseur }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-bold text-uppercase">Référence transfert</div>
                <div class="fw-bold">{{ $paiement->transaction_ref ?: 'Non renseignée' }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small fw-bold text-uppercase">Validation</div>
                <div class="fw-bold">{{ $paiement->reviewed_at ? $paiement->reviewed_at->format('d/m/Y H:i') : 'En attente superadmin' }}</div>
            </div>
        </div>

        @if($paiement->owner_note)
            <div class="alert alert-light border mt-4 mb-0">{{ $paiement->owner_note }}</div>
        @endif

        @if($paiement->review_note)
            <div class="alert alert-info border-0 border-start border-info border-4 mt-4 mb-0">{{ $paiement->review_note }}</div>
        @endif

        @if($paiement->preuve_url)
            <a href="{{ asset(ltrim($paiement->preuve_url, '/')) }}" target="_blank" class="btn theme-action-btn mt-4">
                <i class="bi bi-image me-1"></i>Voir la preuve
            </a>
        @endif
    </div>
</div>
@endsection
