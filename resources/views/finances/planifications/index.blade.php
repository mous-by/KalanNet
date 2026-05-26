@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .finance-menu .nav-link {
        color: var(--text-main);
        border-radius: 8px;
    }
    .finance-menu .nav-link.active {
        border-left: 4px solid var(--theme-primary);
        background: var(--accent-light);
        color: var(--theme-primary);
        font-weight: 700;
    }
    .finance-menu .menu-icon {
        width: 28px;
        height: 28px;
        background: var(--theme-primary);
        color: var(--text-on-accent);
    }
</style>
@endpush

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $isPublicSchool ? 'Coopérative' : 'Planification' }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('finances.index') }}">Finances</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $isPublicSchool ? 'Liste des coopératives' : 'Liste des planifications des paiements' }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('finances.planifications.create') }}" class="btn border-0 border-primary border-4 bg-light-primary text-primary">
        <i class="bi bi-plus-lg"></i> Ajouter
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-4">
    <div class="col-12 col-md-3">
        <div class="card theme-card h-100">
            <div class="card-header theme-header">
                <i class="bi bi-list me-2"></i> Menu
            </div>
            <div class="card-body p-2">
                <ul class="nav flex-column gap-2 finance-menu">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center py-2" href="{{ route('finances.index') }}">
                            <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                <i class="bi bi-graph-up"></i>
                            </span>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active d-flex align-items-center py-2" href="{{ route('finances.planifications') }}">
                            <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                <i class="bi bi-calendar-check"></i>
                            </span>
                            <span>{{ $isPublicSchool ? 'Coopérative' : 'Planification' }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center py-2" href="{{ route('finances.paiements') }}">
                            <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                <i class="bi bi-cash-stack"></i>
                            </span>
                            <span>Paiements</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center py-2" href="{{ route('finances.paiements.historique') }}">
                            <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                <i class="bi bi-clock-history"></i>
                            </span>
                            <span>Historique</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <div class="card theme-card w-100">
            <div class="card-header theme-header">
                <i class="bi bi-cash-coin me-1"></i>
                {{ $isPublicSchool ? 'Liste des coopératives' : 'Liste des planifications' }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('finances.planifications.filter') }}" class="row g-3" id="planificationFilterForm" data-auto-filter="true">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label" for="id_classe">Classe</label>
                        <select class="single-select form-select auto-submit-planification" id="id_classe" name="id_classe">
                            <option value="">Sélectionner une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" @selected(($filters['id_classe'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="id_annee">Année Scolaire</label>
                        <select class="single-select form-select auto-submit-planification" id="id_annee" name="id_annee">
                            <option value="">Sélectionner une année</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="card theme-card w-100 mt-4">
            <div class="card-body">
                <div class="table-responsive mt-3 mb-3">
                    <table class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ $isPublicSchool ? 'Coopérative' : 'Motif' }}</th>
                                <th>{{ $isPublicSchool ? 'Montant coopérative' : 'Coût total' }}</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($planifications as $planification)
                                <tr>
                                    <td>{{ $isPublicSchool ? 'Coopérative' : $planification->motif }}</td>
                                    <td>{{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F CFA</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots"></i>
                                            </a>
                                            <div class="dropdown-menu">
                                                <form method="POST" action="{{ route('finances.planifications.destroy', $planification->id_planification) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item" onclick="return confirm('{{ $isPublicSchool ? 'Supprimer cette coopérative ?' : 'Supprimer cette planification ?' }}')">
                                                        <i class="bi bi-trash me-1"></i> Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">{{ $isPublicSchool ? 'Aucune coopérative à afficher.' : 'Aucune planification à afficher.' }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('planificationFilterForm');
    document.querySelectorAll('.auto-submit-planification').forEach(function (field) {
        field.addEventListener('change', function () {
            const classe = document.getElementById('id_classe')?.value;
            const annee = document.getElementById('id_annee')?.value;
            if (classe && annee) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endsection
