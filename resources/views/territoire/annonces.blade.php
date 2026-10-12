@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ Auth::user()->droit === 'DAE' ? 'Espace DAE' : 'Espace DCAP' }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Annonces</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))<div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>@endif

@if($annoncesAcademie->isNotEmpty())
    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold"><i class="bi bi-bank me-2"></i>Reçues de l'académie</h5>
        </div>
        <div class="card-body">
            @foreach($annoncesAcademie as $annonce)
                <div class="border-bottom py-2">
                    <div class="fw-bold">{{ $annonce->titre }}</div>
                    <div class="small text-muted">{{ \Carbon\Carbon::parse($annonce->date_publication)->format('d/m/Y H:i') }}@if($annonce->auteur) · {{ $annonce->auteur }}@endif</div>
                    <p class="mb-1 mt-1" style="white-space: pre-line;">{{ $annonce->contenu }}</p>
                    @if($annonce->fichier_joint)<a href="{{ asset($annonce->fichier_joint) }}" target="_blank" rel="noopener" class="small"><i class="bi bi-paperclip"></i> Pièce jointe</a>@endif
                </div>
            @endforeach
        </div>
    </div>
@endif

@include('dashboards.partials.annonces-territoire', [
    'titreAnnonces' => Auth::user()->droit === 'DAE' ? "Annonces de l'académie" : 'Annonces du CAP',
    'aideDiffusion' => Auth::user()->droit === 'DAE'
        ? "L'annonce est envoyée aux destinataires choisis dans toutes les écoles de l'académie. « Directeurs de CAP » la réserve aux DCAP."
        : "L'annonce est envoyée aux destinataires choisis dans toutes les écoles du CAP.",
])
@endsection
