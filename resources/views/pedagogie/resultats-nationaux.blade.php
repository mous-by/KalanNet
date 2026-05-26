@extends('layouts.app')

@section('content')
<style>
    .national-results-table-wrap {
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        padding-bottom: 1rem;
    }

    .national-results-table {
        min-width: 760px;
        table-layout: fixed;
    }

    .national-results-table th,
    .national-results-table td {
        vertical-align: middle;
    }

    .national-results-table .student-col {
        width: 220px;
    }

    .national-results-table .matricule-col {
        width: 140px;
    }

    .national-results-table .decision-col {
        width: 150px;
    }

    .national-results-table .moyenne-col {
        width: 120px;
    }

    .national-results-table .observation-col {
        width: 260px;
    }
</style>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Résultats nationaux</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">DEF / BAC</li>
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
    <div class="alert alert-danger border-0 border-start border-danger border-4">
        {{ $errors->first() }}
    </div>
@endif

<div class="card theme-card shadow-sm mb-4">
    <div class="card-header theme-header border-0">
        <h5 class="fw-bold mb-0"><i class="bi bi-award me-2"></i>Saisie des résultats DEF / BAC</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info border-0 border-start border-info border-4">
            Cette zone affiche les élèves de la classe choisie pour saisir ou corriger les résultats à la main. L’import ci-dessous remplit les mêmes résultats automatiquement depuis un fichier.
        </div>
        <form method="GET" action="{{ route('pedagogie.resultats-nationaux.index') }}" class="row g-3" data-national-results-filter>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase">Classe d’examen</label>
                <select name="id_classe" class="form-select" required data-national-class>
                    <option value="">Choisir une classe...</option>
                    @foreach($classes as $classe)
                        @php
                            preg_match('/\d+/', \Illuminate\Support\Str::ascii((string) $classe->nom_classe), $levelMatch);
                            $exam = ((int) ($levelMatch[0] ?? 0)) === 9 ? 'DEF' : 'BAC';
                        @endphp
                        <option value="{{ $classe->id_classe }}" data-exam="{{ $exam }}" @selected(($filters['id_classe'] ?? null) == $classe->id_classe)>
                            {{ $classe->nom_classe }} - {{ $exam }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold text-uppercase">Année scolaire</label>
                <select name="id_annee" class="form-select" required data-national-year>
                    <option value="">Choisir une année...</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee'] ?? null) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-uppercase">Examen</label>
                <select name="niveau_examen" class="form-select" data-national-exam>
                    <option value="">Auto</option>
                    @if(in_array('DEF', $examensDisponibles, true))
                        <option value="DEF" @selected(($niveauExamen ?? null) === 'DEF')>DEF</option>
                    @endif
                    @if(in_array('BAC', $examensDisponibles, true))
                        <option value="BAC" @selected(($niveauExamen ?? null) === 'BAC')>BAC</option>
                    @endif
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card theme-card shadow-sm mb-4">
    <div class="card-header theme-header border-0">
        <h5 class="fw-bold mb-0"><i class="bi bi-upload me-2"></i>Importer des résultats</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-secondary border-0 border-start border-secondary border-4">
            L’import lit un fichier et enregistre les décisions dans la même liste. Après import, choisissez la classe et l’année en haut pour vérifier ou corriger les lignes importées.
        </div>
        <form method="POST" action="{{ route('pedagogie.resultats-nationaux.import') }}" enctype="multipart/form-data" class="row g-3 align-items-end" data-national-results-import>
            @csrf
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase">Classe d’examen</label>
                <select name="id_classe" class="form-select" required data-national-import-class>
                    <option value="">Choisir une classe...</option>
                    @foreach($classes as $classe)
                        @php
                            preg_match('/\d+/', \Illuminate\Support\Str::ascii((string) $classe->nom_classe), $levelMatch);
                            $exam = ((int) ($levelMatch[0] ?? 0)) === 9 ? 'DEF' : 'BAC';
                        @endphp
                        <option value="{{ $classe->id_classe }}" data-exam="{{ $exam }}" @selected(($filters['id_classe'] ?? null) == $classe->id_classe)>
                            {{ $classe->nom_classe }} - {{ $exam }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-uppercase">Année scolaire</label>
                <select name="id_annee" class="form-select" required>
                    <option value="">Choisir une année...</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee'] ?? null) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-uppercase">Examen</label>
                <select name="niveau_examen" class="form-select" required data-national-import-exam>
                    @if(in_array('DEF', $examensDisponibles, true))
                        <option value="DEF" @selected(($niveauExamen ?? null) === 'DEF')>DEF</option>
                    @endif
                    @if(in_array('BAC', $examensDisponibles, true))
                        <option value="BAC" @selected(($niveauExamen ?? null) === 'BAC')>BAC</option>
                    @endif
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-uppercase">Date résultat</label>
                <input type="date" name="date_resultat" class="form-control" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase">Fichier Excel, CSV ou PDF texte</label>
                <input type="file" name="fichier_resultats" class="form-control" accept=".xlsx,.xls,.csv,.txt,.pdf" required>
                <div class="small text-muted mt-1">Colonnes : Matricule, Décision, Moyenne, Observation.</div>
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-outline-primary fw-bold px-4">Importer</button>
            </div>
        </form>
    </div>
</div>

@if($selectedClasse && ($filters['id_annee'] ?? null) && $niveauExamen)
    <form method="POST" action="{{ route('pedagogie.resultats-nationaux.store') }}">
        @csrf
        <input type="hidden" name="id_classe" value="{{ $selectedClasse->id_classe }}">
        <input type="hidden" name="id_annee" value="{{ $filters['id_annee'] }}">
        <input type="hidden" name="niveau_examen" value="{{ $niveauExamen }}">

        <div class="card theme-card shadow-sm">
            <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold mb-0">{{ $selectedClasse->nom_classe }} - {{ $niveauExamen }}</h5>
                    <div class="small">Résultats utilisés ensuite par la réinscription intelligente.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="date" name="date_resultat" class="form-control" value="{{ now()->toDateString() }}">
                    <button type="submit" class="btn btn-primary fw-bold px-4">Enregistrer</button>
                </div>
            </div>

            @if($eleves->isEmpty())
                <div class="card-body text-center py-5">
                    <i class="bi bi-person-x fs-1 d-block mb-3" style="color: var(--theme-accent);"></i>
                    <h5 class="fw-bold mb-2">Aucun élève trouvé</h5>
                    <p class="text-muted mb-0">Aucun élève actif n’est inscrit dans cette classe pour l’année choisie.</p>
                </div>
            @else
                <div class="national-results-table-wrap">
                    <table class="table table-bordered align-middle mb-0 national-results-table">
                        <thead class="table-light">
                            <tr>
                                <th class="student-col">Élève</th>
                                <th class="matricule-col">Matricule</th>
                                <th class="decision-col">Décision</th>
                                <th class="moyenne-col">Moyenne</th>
                                <th class="observation-col">Observation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eleves as $eleve)
                                @php($resultat = $resultats->get($eleve->id_eleve))
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }}</div>
                                    </td>
                                    <td>{{ $eleve->matricule ?: 'Non renseigné' }}</td>
                                    <td>
                                        <select name="resultats[{{ $eleve->id_eleve }}][decision]" class="form-select form-select-sm">
                                            <option value="">En attente</option>
                                            <option value="admis" @selected(($resultat->decision ?? null) === 'admis')>Admis</option>
                                            <option value="échec" @selected(($resultat->decision ?? null) === 'échec')>Échec</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="resultats[{{ $eleve->id_eleve }}][moyenne]" class="form-control form-control-sm" min="0" max="20" step="0.01" value="{{ $resultat->moyenne ?? '' }}">
                                    </td>
                                    <td>
                                        <input type="text" name="resultats[{{ $eleve->id_eleve }}][observation]" class="form-control form-control-sm" maxlength="255" value="{{ $resultat->observation ?? '' }}" placeholder="Observation">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white border-0 d-flex justify-content-end p-3">
                    <button type="submit" class="btn btn-primary fw-bold px-4">Enregistrer</button>
                </div>
            @endif
        </div>
    </form>
@else
    <div class="card theme-card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-filter-circle fs-1 d-block mb-3" style="color: var(--theme-accent);"></i>
            <h5 class="fw-bold mb-2">Choisissez une classe d’examen</h5>
            <p class="text-muted mb-0">Les classes 9ème alimentent le DEF, les classes 12ème alimentent le BAC.</p>
        </div>
    </div>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.querySelector('[data-national-results-filter]');
    const classSelect = filterForm?.querySelector('[data-national-class]');
    const yearSelect = filterForm?.querySelector('[data-national-year]');
    const examSelect = filterForm?.querySelector('[data-national-exam]');

    const syncExamFromClass = (sourceClassSelect, targetExamSelect) => {
        if (!sourceClassSelect || !targetExamSelect) return;
        const selectedOption = sourceClassSelect.options[sourceClassSelect.selectedIndex];
        const exam = selectedOption?.dataset.exam || '';
        if (exam && Array.from(targetExamSelect.options).some((option) => option.value === exam)) {
            targetExamSelect.value = exam;
        }
    };

    const submitWhenReady = () => {
        if (!filterForm || !classSelect?.value || !yearSelect?.value) return;
        syncExamFromClass(classSelect, examSelect);
        filterForm.submit();
    };

    classSelect?.addEventListener('change', submitWhenReady);
    yearSelect?.addEventListener('change', submitWhenReady);
    examSelect?.addEventListener('change', submitWhenReady);

    const importClassSelect = document.querySelector('[data-national-import-class]');
    const importExamSelect = document.querySelector('[data-national-import-exam]');
    importClassSelect?.addEventListener('change', () => syncExamFromClass(importClassSelect, importExamSelect));
    syncExamFromClass(importClassSelect, importExamSelect);
});
</script>
@endsection
