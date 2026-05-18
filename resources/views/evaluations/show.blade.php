@extends('layouts.app')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between bg-card p-4 rounded-4 shadow-sm">
                <div class="d-flex align-items-center">
                    <a href="{{ route('evaluations.index') }}" class="btn btn-light rounded-circle p-2 me-3">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h2 class="mb-1 fw-bold">{{ $evaluation->libeller }}</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('evaluations.index') }}">Évaluations</a></li>
                                <li class="breadcrumb-item active">Notes</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary rounded-pill px-4"><i class="bi bi-printer me-2"></i>Imprimer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-info text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold small mb-3">Matière</h6>
                            <h4 class="fw-bold mb-0">{{ $matiere->nom_matiere }}</h4>
                        </div>
                        <div class="widget-icon bg-white text-info rounded-3">
                            <i class="bi bi-journal-text fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-primary text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold small mb-3">Classe</h6>
                            <h4 class="fw-bold mb-0">{{ $classe->nom_classe }}</h4>
                        </div>
                        <div class="widget-icon bg-white text-primary rounded-3">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm h-100 bg-success text-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-white-50 text-uppercase fw-bold small mb-3">Moyenne de Classe</h6>
                            <h4 class="fw-bold mb-0">{{ number_format($details->whereNotNull('note')->avg('note') ?? 0, 2) }} / 20</h4>
                        </div>
                        <div class="widget-icon bg-white text-success rounded-3">
                            <i class="bi bi-bar-chart-line fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3 border-0 small fw-bold text-muted text-uppercase">Matricule</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Élève</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Note</th>
                        <th class="px-4 py-3 border-0 text-end small fw-bold text-muted text-uppercase">Appréciation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($details as $line)
                        <tr>
                            <td class="px-4 py-3 text-muted">{{ $line->eleve->matricule }}</td>
                            <td class="fw-bold">{{ $line->eleve->nom_eleve }} {{ $line->eleve->prenom_eleve }}</td>
                            <td>
                                @if($line->note === null)
                                    <span class="badge rounded-pill px-3 py-2 bg-secondary-soft text-secondary" style="font-size: 1rem;">Non saisie</span>
                                @else
                                    <span class="badge rounded-pill px-3 py-2 {{ $line->note >= 10 ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }}" style="font-size: 1rem;">
                                        {{ number_format($line->note, 2) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 text-end">
                                @if($line->note === null)
                                    <span class="text-muted">En attente</span>
                                @elseif($line->note >= 16)
                                    <span class="text-success fw-bold">Très Bien</span>
                                @elseif($line->note >= 14)
                                    <span class="text-success">Bien</span>
                                @elseif($line->note >= 12)
                                    <span class="text-primary">Assez Bien</span>
                                @elseif($line->note >= 10)
                                    <span class="text-dark">Passable</span>
                                @else
                                    <span class="text-danger">Insuffisant</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .widget-icon { width: 54px; height: 54px; display: flex; align-items: center; justify-content: center; }
    </style>
@endsection
