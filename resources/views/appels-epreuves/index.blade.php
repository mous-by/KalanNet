@extends('layouts.app')

@section('content')
<style>
    .appels-theme-btn {
        background-color: var(--theme-accent) !important;
        border-color: var(--theme-accent) !important;
        color: var(--text-on-accent, #fff) !important;
    }

    .appels-theme-btn i,
    .appels-theme-btn span {
        color: inherit !important;
    }

    .appels-back-btn {
        color: var(--theme-accent) !important;
        border-color: var(--theme-accent) !important;
        background: transparent !important;
    }

    .appels-back-btn:hover {
        background-color: var(--theme-accent) !important;
        color: var(--text-on-accent, #fff) !important;
    }
</style>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('dashboard') }}" class="btn btn-sm appels-back-btn" title="Retour">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0">Historique des appels</h5>
</div>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Appels d'épreuves</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Historique</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
@endif

<div class="card theme-card shadow-sm mb-4">
    <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="fw-bold mb-0"><i class="bi bi-clipboard-check me-2"></i>Historique des appels</h5>
        <a href="{{ route('appels-epreuves.create') }}" class="btn appels-theme-btn"><i class="bi bi-plus-lg me-1"></i>Nouvel appel</a>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('appels-epreuves.index') }}" class="row g-3" data-auto-filter="true">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Classe</label>
                <select name="id_classe" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id_classe }}" @selected(($filters['id_classe'] ?? null) == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Matière</label>
                <select name="id_matiere" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($matieres as $matiere)
                        <option value="{{ $matiere->id_matiere }}" @selected(($filters['id_matiere'] ?? null) == $matiere->id_matiere)>{{ $matiere->nom_matiere }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Année</label>
                <select name="id_annee_scolaire" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee_scolaire'] ?? null) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Période</label>
                <select name="id_trimestre" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($trimestres as $trimestre)
                        <option value="{{ $trimestre->id_trimestre }}" @selected(($filters['id_trimestre'] ?? null) == $trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card theme-card shadow-sm">
    @unless($hasFilters)
        <div class="card-body text-center py-5">
            <i class="bi bi-funnel fs-1 d-block mb-3" style="color: var(--theme-accent);"></i>
            <h5 class="fw-bold mb-2">Choisissez un filtre pour afficher l'historique</h5>
            <p class="text-muted mb-0">
                Sélectionnez une classe, une matière, une année ou une période. Les appels enregistrés apparaîtront ici après votre choix.
            </p>
        </div>
    @else
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Épreuve</th>
                    <th>Élève</th>
                    <th>Classe</th>
                    <th>Année</th>
                    <th>Matière</th>
                    <th>Statut</th>
                    <th>Pénalité</th>
                    <th>Conduite</th>
                    <th>Parent</th>
                </tr>
            </thead>
            <tbody>
            @forelse($appels as $appel)
                <tr>
                    <td>{{ $appel->date?->format('d/m/Y') }}</td>
                    <td>
                        <div class="fw-bold">{{ $appel->libelle }}</div>
                        <small class="text-muted">{{ substr($appel->heure_debut, 0, 5) }} - {{ substr($appel->heure_fin, 0, 5) }}</small>
                    </td>
                    <td>{{ $appel->eleve?->nom_eleve }} {{ $appel->eleve?->prenom_eleve }}</td>
                    <td>{{ $appel->classe?->nom_classe }}</td>
                    <td>{{ $appel->annee?->annee ?? 'Année #' . $appel->id_annee_scolaire }}</td>
                    <td>{{ $appel->matiere?->nom_matiere ?? 'Matière #' . $appel->id_matiere }}</td>
                    <td><span class="badge bg-warning">{{ $appel->statutControle?->type_controle }}</span></td>
                    <td class="text-danger fw-bold">-{{ abs((float) ($appel->statutControle?->penalite_conduite ?? 0)) }}</td>
                    <td>
                        <div class="fw-bold">
                            {{ number_format((float) $appel->note_conduite_avant, 2, ',', ' ') }}
                            <i class="bi bi-arrow-right mx-1 text-muted"></i>
                            {{ number_format((float) $appel->note_conduite_apres, 2, ',', ' ') }}
                        </div>
                        <small class="text-muted">Note bulletin dynamique</small>
                    </td>
                    <td>{{ $appel->notifier_parent ? 'Notifié' : 'Non' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted py-4">Aucun appel d'épreuve enregistré.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($appels->hasPages())
        <div class="card-body">{{ $appels->links() }}</div>
    @endif
    @endunless
</div>
@endsection
