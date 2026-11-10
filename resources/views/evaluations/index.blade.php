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

    @if($compositionsEnDouble->isNotEmpty())
        <div class="alert alert-warning border-0 border-start border-warning border-4">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1"></i>Plusieurs compositions dans un même trimestre : le bulletin n'en retient qu'une.</div>
            <ul class="mb-2 small">
                @foreach($compositionsEnDouble as $d)
                    <li><strong>{{ $d->nom_classe }}</strong>, {{ $d->nom_matiere }}, {{ $d->nom_trimestre }} : {{ $d->evaluations }}</li>
                @endforeach
            </ul>
            <div class="small">Si l'une d'elles est une <strong>composition mensuelle</strong> (Fondamental II), ouvrez « Modifier la programmation » de cette évaluation et choisissez son <strong>mois</strong> : elle aura son bulletin du mois et ne comptera plus dans le trimestre. Les notes déjà saisies et leur validation sont gardées.</div>
        </div>
    @endif

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
                        <option value="">Toutes les années</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee_scolaire'] ?? null) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <select class="form-select" name="periode">
                        <option value="en_cours" @selected($filters['periode'] === 'en_cours')>En cours (récentes et à venir)</option>
                        <option value="tout" @selected($filters['periode'] === 'tout')>Toute l'année</option>
                        <optgroup label="Trimestres">
                            @foreach($trimestres as $trimestre)
                                <option value="t{{ $trimestre->id_trimestre }}" @selected($filters['periode'] === 't'.$trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Mois (Fondamental I, compositions mensuelles du Fondamental II)">
                            @foreach([9, 10, 11, 12, 1, 2, 3, 4, 5, 6] as $m)
                                @if(isset($moisOptions[$m]))
                                    <option value="m{{ $m }}" @selected($filters['periode'] === 'm'.$m)>{{ $moisOptions[$m] }}</option>
                                @endif
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <a href="{{ route('evaluations.index') }}" class="btn btn-light w-100">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    @php
        $periodeLibelle = match (true) {
            $filters['periode'] === 'en_cours' => 'en cours : depuis le '.\Carbon\Carbon::parse($depuis)->format('d/m/Y').' et à venir',
            $filters['periode'] === 'tout' => 'toute la période',
            str_starts_with($filters['periode'], 't') => optional($trimestres->firstWhere('id_trimestre', (int) substr($filters['periode'], 1)))->nom_trimestre ?? 'trimestre',
            default => 'mois de '.mb_strtolower($moisOptions[(int) substr($filters['periode'], 1)] ?? ''),
        };
        $anneeLibelle = optional($annees->firstWhere('id_anneeScolaire', (int) ($filters['id_annee_scolaire'] ?? 0)))->annee ?? 'toutes les années';
    @endphp
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2 small">
        <span><i class="bi bi-funnel me-1"></i><strong>{{ $evaluations->total() }}</strong> évaluation(s) affichée(s) — {{ $anneeLibelle }}, {{ $periodeLibelle }}.</span>
        @if($filters['periode'] !== 'tout')
            <a href="{{ route('evaluations.index', array_filter(['id_classe' => $filters['id_classe'] ?? null, 'id_matiere' => $filters['id_matiere'] ?? null]) + ['id_annee_scolaire' => $filters['id_annee_scolaire'] ?? '', 'periode' => 'tout']) }}">Tout afficher pour l'année</a>
        @endif
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
                            <th>Type</th>
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
                                @php
                                    $estCompo = $ligne->type_note === 'composition';
                                    $mensuelle = $estCompo && $ligne->mois && ($ligne->classe->ordreEnseignement ?? null) !== 'fondamentale1';
                                @endphp
                                <td>
                                    @if($mensuelle)
                                        <span class="badge bg-info text-dark">Composition mensuelle</span>
                                    @elseif($estCompo)
                                        <span class="badge bg-primary">Composition</span>
                                    @elseif($ligne->type_note === 'devoir')
                                        <span class="badge bg-secondary">Devoir</span>
                                    @elseif($ligne->type_note === 'nt10')
                                        <span class="badge bg-secondary">Note sur 10</span>
                                    @endif
                                </td>
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
                            <tr><td colspan="10" class="text-center py-4 text-muted">
                                Aucune évaluation pour cette recherche.
                                @if($filters['periode'] === 'en_cours')
                                    <div class="mt-1">Seules les évaluations récentes et à venir sont affichées : choisissez « Toute l'année », un trimestre ou un mois dans « Période ».</div>
                                @endif
                            </td></tr>
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
            const form = document.getElementById('evaluation-filter-form');
            let readyToSubmit = false;

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
