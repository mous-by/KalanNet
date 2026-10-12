@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Pédagogie</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Évaluations communes</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))<div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-warning border-0 border-start border-warning border-4">{{ session('error') }}</div>@endif

<div class="card theme-card shadow-sm">
    <div class="card-header theme-header">
        <h5 class="mb-0 fw-bold"><i class="bi bi-journal-check me-2"></i>Évaluations communes de l'académie et du CAP</h5>
    </div>
    <div class="card-body">
        <p class="small text-muted">Le sujet est confidentiel : il devient téléchargeable à l'heure fixée par l'académie ou le CAP. Imprimez-le pour l'épreuve et ne le diffusez pas avant.</p>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Évaluation</th><th>Niveau · matière</th><th>Épreuve</th><th>État</th><th>Documents</th></tr>
                </thead>
                <tbody>
                    @forelse($evaluations as $evaluation)
                        <tr>
                            <td class="fw-semibold">{{ $evaluation->titre }}<div class="small text-muted fw-normal">{{ $evaluation->origine === 'cap' ? 'Proposée par le CAP' : "Proposée par l'académie" }}</div>
                                @if($evaluation->consignes)<div class="small fw-normal mt-1" style="white-space: pre-line;">{{ $evaluation->consignes }}</div>@endif
                            </td>
                            <td>{{ $evaluation->niveau_libelle ?? 'Tous niveaux' }}<div class="small text-muted">{{ $evaluation->matiere_libelle }}</div></td>
                            <td>{{ \Carbon\Carbon::parse($evaluation->debut_at)->format('d/m/Y H:i') }}<div class="small text-muted">{{ $evaluation->duree_minutes }} min</div></td>
                            <td>@include('evaluations-communes.partials.etat')</td>
                            <td class="text-nowrap">
                                @if($evaluation->sujet_disponible)
                                    <a href="{{ route('evaluations-communes.fichier', [$evaluation->id, 'sujet']) }}" class="btn btn-sm btn-primary"><i class="bi bi-download me-1"></i>Sujet</a>
                                    @if(isset($accuses[$evaluation->id]) && $accuses[$evaluation->id]->telecharge_at)
                                        <div class="small text-success mt-1"><i class="bi bi-check-circle-fill"></i> Téléchargé</div>
                                    @endif
                                @else
                                    <span class="small text-muted"><i class="bi bi-lock"></i> Disponible le {{ \Carbon\Carbon::parse($evaluation->liberation_sujet_at)->format('d/m/Y à H:i') }}</span>
                                @endif
                                @if($evaluation->corrige_disponible)
                                    <a href="{{ route('evaluations-communes.fichier', [$evaluation->id, 'corrige']) }}" class="btn btn-sm btn-outline-primary mt-1"><i class="bi bi-download me-1"></i>Corrigé</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucune évaluation commune pour votre école.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
