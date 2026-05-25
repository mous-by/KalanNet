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
    <a href="{{ route('appels-epreuves.index') }}" class="btn btn-sm appels-back-btn" title="Retour">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="fw-bold mb-0">Nouvel appel d'épreuve</h5>
</div>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Appels d'épreuves</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('appels-epreuves.index') }}">Historique</a></li>
                <li class="breadcrumb-item active" aria-current="page">Nouvel appel</li>
            </ol>
        </nav>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
@endif

<div class="card theme-card shadow-sm mb-4">
    <div class="card-header theme-header">
        <h5 class="fw-bold mb-0"><i class="bi bi-ui-checks-grid me-2"></i>Préparer l'appel</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('appels-epreuves.create') }}" class="row g-3" data-auto-filter="true">
            <div class="col-md-6">
                <label class="form-label fw-bold">Classe</label>
                <select name="id_classe" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id_classe }}" @selected($selectedClasse == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold">Année scolaire</label>
                <select name="id_annee_scolaire" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id_anneeScolaire }}" @selected($selectedAnnee == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

@if($selectedClasse && $selectedAnnee)
<form method="POST" action="{{ route('appels-epreuves.store') }}">
    @csrf
    <input type="hidden" name="id_classe" value="{{ $selectedClasse }}">
    <input type="hidden" name="id_annee_scolaire" value="{{ $selectedAnnee }}">

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header">
            <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square me-2"></i>Informations de l'épreuve</h5>
        </div>
        <div class="card-body row g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Matière</label>
                <select name="id_matiere" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach($matieres as $matiere)
                        <option value="{{ $matiere->id_matiere }}" @selected(old('id_matiere') == $matiere->id_matiere)>{{ $matiere->nom_matiere }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Période</label>
                <select name="id_trimestre" class="form-select" required>
                    <option value="">Choisir</option>
                    @foreach($trimestres as $trimestre)
                        <option value="{{ $trimestre->id_trimestre }}" @selected(old('id_trimestre') == $trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Date</label>
                <input type="date" name="date" class="form-control" value="{{ old('date', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Libellé</label>
                <input name="libelle" class="form-control" value="{{ old('libelle') }}" placeholder="Devoir, composition..." required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Début</label>
                <input type="time" name="heure_debut" class="form-control" value="{{ old('heure_debut', '08:00') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Fin</label>
                <input type="time" name="heure_fin" class="form-control" value="{{ old('heure_fin', '10:00') }}" required>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="notifier_parent" value="1" id="notifier-parent" checked>
                    <label class="form-check-label fw-bold" for="notifier-parent">Notifier</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Appel des élèves</h5>
            <button class="btn btn-primary shadow-sm" type="submit"><i class="bi bi-check2-circle me-1"></i>Enregistrer</button>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th style="width: 280px;">Statut</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($eleves as $eleve)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }}</div>
                            <small class="text-muted">{{ $eleve->matricule }}</small>
                        </td>
                        <td>
                            <select name="statuts[{{ $eleve->id_eleve }}]" class="form-select" required>
                                @foreach($statuts as $statut)
                                    <option value="{{ $statut->id_controle }}">{{ $statut->type_controle }}{{ abs((float) $statut->penalite_conduite) > 0 ? ' (-'.abs((float) $statut->penalite_conduite).' conduite)' : '' }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">Aucun élève actif trouvé pour cette classe et cette année.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end">
            <button class="btn btn-primary shadow-sm px-4" type="submit"><i class="bi bi-check2-circle me-1"></i>Enregistrer</button>
        </div>
    </div>
</form>
@endif
@endsection
