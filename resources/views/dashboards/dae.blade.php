@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4 mt-2">
    <div class="breadcrumb-title pe-3">Espace DAE</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $academie->nom_academie ?? 'Académie' }}</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-warning border-0 border-start border-warning border-4">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
@endif

<div class="mb-3">
    <h4 class="fw-bold mb-1">{{ $academie->nom_academie ?? 'Académie' }}</h4>
    <div class="text-muted">Vue d'ensemble de l'académie{{ !empty($academie->localite_academie) ? ' · ' . $academie->localite_academie : '' }}</div>
</div>

<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    @include('dashboards.partials.chiffre', ['icone' => 'bi-diagram-3', 'libelle' => 'CAP', 'valeur' => $stats['caps']])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-building', 'libelle' => 'Écoles', 'valeur' => $stats['ecoles'], 'detail' => $stats['ecoles_publiques'] . ' publique(s) · ' . ($stats['ecoles'] - $stats['ecoles_publiques']) . ' privée(s)'])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-person-badge', 'libelle' => 'Enseignants', 'valeur' => $stats['enseignants']])
    @include('dashboards.partials.chiffre', ['icone' => 'bi-people', 'libelle' => 'Élèves', 'valeur' => $stats['eleves'], 'detail' => $stats['classes'] . ' classe(s)'])
</div>

<div class="card theme-card shadow-sm mb-4">
    <div class="card-header theme-header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3 me-2"></i>Répartition par CAP</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>CAP</th>
                        <th>Directeur (DCAP)</th>
                        <th class="text-end">Écoles</th>
                        <th class="text-end">Enseignants</th>
                        <th class="text-end">Élèves</th>
                    </tr>
                </thead>
                <tbody style="font-variant-numeric: tabular-nums;">
                    @forelse($stats['par_cap'] as $cap)
                        <tr>
                            <td class="fw-semibold">{{ $cap->nom_cap }}@if($cap->localite)<div class="small text-muted fw-normal">{{ $cap->localite }}</div>@endif</td>
                            <td>{{ $cap->directeur ?? '—' }}</td>
                            <td class="text-end">{{ $cap->ecoles }}</td>
                            <td class="text-end">{{ number_format($cap->enseignants, 0, ',', ' ') }}</td>
                            <td class="text-end">{{ number_format($cap->eleves, 0, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">Aucun CAP rattaché à cette académie.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('dashboards.partials.annonces-territoire', [
    'titreAnnonces' => "Annonces de l'académie",
    'aideDiffusion' => "L'annonce est envoyée aux destinataires choisis dans toutes les écoles de l'académie. « Directeurs de CAP » la réserve aux DCAP.",
])
@endsection
