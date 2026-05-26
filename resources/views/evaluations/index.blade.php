@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Évaluations</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste des évaluations</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3">Rechercher une évaluation</h5>
            <form method="GET" action="{{ route('evaluations.index') }}" class="row g-3" id="evaluation-filter-form" data-auto-filter="true">
                <div class="col-md-3">
                    <label class="form-label">Classe</label>
                    <select class="form-select" id="id_classe" name="id_classe">
                        <option value="">Choisir une classe</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" data-ordre="{{ $classe->ordreEnseignement }}" @selected(($filters['id_classe'] ?? null) == $classe->id_classe)>
                                {{ $classe->nom_classe }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Matière</label>
                    <select class="form-select" id="id_matiere" name="id_matiere" data-selected="{{ $filters['id_matiere'] ?? '' }}">
                        <option value="">Sélectionnez une matière</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Année scolaire</label>
                    <select class="form-select" name="id_annee_scolaire">
                        <option value="">Choisir une année</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee_scolaire'] ?? null) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 trimestre-field">
                    <label class="form-label">Période</label>
                    <select class="form-select" name="id_trimestre">
                        <option value="">Choisir une période</option>
                        @foreach($trimestres as $trimestre)
                            <option value="{{ $trimestre->id_trimestre }}" @selected(($filters['id_trimestre'] ?? null) == $trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mois-field">
                    <label class="form-label">Mois</label>
                    <select class="form-select" name="mois">
                        <option value="">Sélectionner un mois</option>
                        @foreach($moisOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['mois'] ?? null) == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="{{ route('evaluations.index') }}" class="btn btn-light w-100">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    @if(auth()->user()->userHasPermission('evaluation_création'))
        <div class="text-end mb-3">
            <a href="{{ route('evaluations.create') }}" class="btn btn-sm d-inline-flex align-items-center gap-1 shadow-sm text-white" style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;">
                <i class="bi bi-plus-lg"></i>
                <span>Préparer une évaluation</span>
            </a>
        </div>
    @endif

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Date évaluation</th>
                            <th>Libellé</th>
                            <th>Classe</th>
                            <th>Matière</th>
                            <th>Période</th>
                            <th>Validation</th>
                            <th>Heure début</th>
                            <th>Heure fin</th>
                            <th class="text-center">Ce que vous voulez faire</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($evaluations as $ligne)
                            <tr>
                                <td>{{ $ligne->evaluation->date_evaluation }}</td>
                                <td class="fw-bold">{{ $ligne->evaluation->libeller }}</td>
                                <td>{{ $ligne->classe->nom_classe ?? '' }}</td>
                                <td>{{ $ligne->matiere->nom_matiere ?? '' }}</td>
                                <td>{{ $ligne->mois ? ($moisOptions[(int) $ligne->mois] ?? $ligne->mois) : ($ligne->trimestre->nom_trimestre ?? '') }}</td>
                                <td>
                                    @if(($ligne->validation_status ?? 'valide') === 'en_attente')
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    @else
                                        <span class="badge bg-success">Validée</span>
                                    @endif
                                </td>
                                <td>{{ $ligne->evaluation->heure_debut }}</td>
                                <td>{{ $ligne->evaluation->heure_fin }}</td>
                                <td class="text-center">
                                    <a href="{{ route('evaluations.show', $ligne->id_evaluation) }}" class="btn btn-sm btn-outline-info mb-1" title="Voir les détails">
                                        <i class="bi bi-eye me-1"></i>Voir
                                    </a>
                                    @if(auth()->user()->userHasPermission('evaluation_modification'))
                                        <a href="{{ route('evaluations.programme.edit', $ligne->id_evaluation) }}" class="btn btn-sm btn-outline-warning mb-1" title="Changer la date, la classe, la matière ou la période">
                                            <i class="bi bi-calendar-event me-1"></i>Modifier la fiche
                                        </a>
                                        <a href="{{ route('evaluations.edit', $ligne->id_evaluation) }}" class="btn btn-sm btn-outline-success mb-1" title="Entrer ou corriger les notes des élèves">
                                            <i class="bi bi-journal-check me-1"></i>Saisir les notes
                                        </a>
                                    @endif
                                    @if(auth()->user()->userHasAnyPermission(['evaluation_validation_notes', 'valider_note_saisi', 'valider_notes_saisies']) && ($ligne->validation_status ?? 'valide') === 'en_attente')
                                        <form action="{{ route('evaluations.validate-notes', $ligne->id_evaluation) }}" method="POST" class="d-inline validate-notes-form">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-primary mb-1" title="Valider les notes">
                                                <i class="bi bi-check2-circle me-1"></i>Valider
                                            </button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->userHasPermission('evaluation_supprimer'))
                                        <form action="{{ route('evaluations.destroy', $ligne->id_evaluation) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette évaluation ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger mb-1" title="Supprimer">
                                                <i class="bi bi-trash me-1"></i>Supprimer
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4 text-muted">Aucune évaluation trouvée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($evaluations->hasPages())
                <div class="mt-4">{{ $evaluations->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const classeSelect = document.getElementById('id_classe');
            const matiereSelect = document.getElementById('id_matiere');
            const selectedMatiere = matiereSelect.dataset.selected;
            const trimestreField = document.querySelector('.trimestre-field');
            const moisField = document.querySelector('.mois-field');
            const form = document.getElementById('evaluation-filter-form');
            let readyToSubmit = false;

            function syncPeriodMode() {
                const ordre = classeSelect.options[classeSelect.selectedIndex]?.dataset.ordre || '';
                const isFondamentale1 = ordre === 'fondamentale1';
                trimestreField.style.display = isFondamentale1 ? 'none' : '';
                moisField.style.display = isFondamentale1 ? '' : 'none';
                trimestreField.querySelectorAll('select').forEach(select => select.disabled = isFondamentale1);
                moisField.querySelectorAll('select').forEach(select => select.disabled = !isFondamentale1);
            }

            function loadMatieres() {
                const idClasse = classeSelect.value;
                matiereSelect.innerHTML = '<option value="">Sélectionnez une matière</option>';
                if (!idClasse) return;

                fetch("{{ url('/evaluations/classes') }}/" + idClasse + "/matieres")
                    .then(response => response.json())
                    .then(data => {
                        (data.matiere || []).forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id_matiere;
                            option.textContent = item.nom_matiere;
                            option.selected = String(item.id_matiere) === String(selectedMatiere);
                            matiereSelect.appendChild(option);
                        });
                    });
            }

            classeSelect.addEventListener('change', function () {
                syncPeriodMode();
                loadMatieres();
                submitFilters();
            });
            form.querySelectorAll('select').forEach(select => {
                if (select !== classeSelect) {
                    select.addEventListener('change', submitFilters);
                }
            });
            function submitFilters() {
                if (readyToSubmit) form.requestSubmit();
            }
            syncPeriodMode();
            loadMatieres();
            readyToSubmit = true;
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.validate-notes-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    if (!window.Swal) {
                        if (confirm('Valider ces notes pour les bulletins ?')) {
                            form.submit();
                        }
                        return;
                    }

                    Swal.fire({
                        title: 'Valider ces notes ?',
                        text: 'Valider ces notes pour les bulletins ?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Oui, valider',
                        cancelButtonText: 'Annuler',
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d',
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endpush
