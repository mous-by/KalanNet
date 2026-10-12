@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4 mt-2">
    <div class="breadcrumb-title pe-3">Espace DCAP</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $cap->nom_cap ?? 'CAP' }}</li>
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

<div class="mb-3">
    <h4 class="fw-bold mb-1">{{ $cap->nom_cap ?? 'CAP' }}</h4>
    <div class="text-muted">{{ $cap->nom_academie ?? '' }}{{ !empty($cap->localite_cap) ? ' · ' . $cap->localite_cap : '' }}</div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    @include('dashboards.partials.chiffre', ['icone' => 'bi-building', 'libelle' => 'Écoles', 'valeur' => $stats['ecoles'], 'detail' => $stats['ecoles_publiques'] . ' publique(s) · ' . $stats['ecoles_privees'] . ' privée(s)'])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-person-badge', 'libelle' => 'Enseignants', 'valeur' => $stats['enseignants']])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-people', 'libelle' => 'Élèves', 'valeur' => $stats['eleves']])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-grid-3x3-gap', 'libelle' => 'Classes', 'valeur' => $stats['classes']])
</div>

<div class="card theme-card shadow-sm mb-4">
    <div class="card-header theme-header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-building me-2"></i>Écoles du CAP</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>École</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Contact</th>
                        <th class="text-end">Enseignants</th>
                        <th class="text-end">Élèves</th>
                    </tr>
                </thead>
                <tbody style="font-variant-numeric: tabular-nums;">
                    @forelse($stats['liste_ecoles'] as $ecole)
                        <tr>
                            <td class="fw-semibold">{{ $ecole->nom }}@if($ecole->adresse)<div class="small text-muted fw-normal">{{ $ecole->adresse }}</div>@endif</td>
                            <td>{{ $ecole->type }}</td>
                            <td><span class="badge {{ $ecole->publique ? 'bg-info text-dark' : 'bg-light text-dark border' }}">{{ $ecole->publique ? 'Publique' : 'Privée' }}</span></td>
                            <td class="small">{{ $ecole->telephone }}@if($ecole->email)<div>{{ $ecole->email }}</div>@endif</td>
                            <td class="text-end">{{ number_format($ecole->enseignants, 0, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($ecole->eleves, 0, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted text-center py-3">Aucune école rattachée à ce CAP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($annoncesAcademie->isNotEmpty())
    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold"><i class="bi bi-bank me-2"></i>Annonces de l'académie</h5>
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
    'titreAnnonces' => 'Annonces du CAP',
    'aideDiffusion' => "L'annonce est envoyée aux destinataires choisis dans toutes les écoles du CAP.",
])
@endsection
