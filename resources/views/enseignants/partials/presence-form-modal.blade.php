@php
    $modalLecons = collect(old('lecons', $presence?->lecons->map(fn ($lecon) => [
        'titre' => $lecon->titre,
        'nombre_heure' => $lecon->nombre_heure,
        'progression' => $lecon->progression,
    ])->toArray() ?? [['titre' => '', 'nombre_heure' => '', 'progression' => 0]]));
    $authUser = Auth::user();
    $isTeacher = $authUser->droit === 'enseignant';
    $selectedTeacher = old('id_enseignant', $presence->id_enseignant ?? $authUser->id_enseignant);
    $selectedClass = old('id_classe', $presence->id_classe ?? null);
    $selectedAnnee = old('id_anneeScolaire', $presence->id_anneeScolaire ?? $currentAcademicYearId);
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ $action }}" method="POST" class="presence-dynamic-form"
                  data-form-data='@json($presenceFormData)'
                  data-selected-teacher="{{ $selectedTeacher }}"
                  data-selected-class="{{ $selectedClass }}">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif
                <div class="modal-header theme-header">
                    <h5 class="modal-title fw-bold">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Enseignant</label>
                            <select name="id_enseignant" class="form-select js-enseignant-select" required @disabled($isTeacher)>
                                <option value="">Sélectionner</option>
                                @foreach($enseignants as $enseignant)
                                    <option value="{{ $enseignant->id_enseignant }}" @selected(old('id_enseignant', $presence->id_enseignant ?? Auth::user()->id_enseignant) == $enseignant->id_enseignant)>
                                        {{ $enseignant->nom_prenom_enseignant }}
                                    </option>
                                @endforeach
                            </select>
                            @if($isTeacher)
                                <input type="hidden" name="id_enseignant" value="{{ $authUser->id_enseignant }}">
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date et heure</label>
                            <input type="datetime-local" name="date_presence" class="form-control"
                                   value="{{ old('date_presence', optional($presence?->date_presence)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Classe</label>
                            <select name="id_classe" class="form-select js-classe-select" required>
                                <option value="">Sélectionner</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre total d'heures</label>
                            <input type="number" name="nombre_heure" class="form-control" min="0.25" max="24" step="0.25"
                                   value="{{ old('nombre_heure', $presence->nombre_heure ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trimestre</label>
                            <select name="id_trimestre" class="form-select" required>
                                <option value="">Sélectionner</option>
                                @foreach($trimestres as $trimestre)
                                    <option value="{{ $trimestre->id_trimestre }}" @selected(old('id_trimestre', $presence->id_trimestre ?? null) == $trimestre->id_trimestre)>
                                        {{ $trimestre->nom_trimestre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Année scolaire</label>
                            <select name="id_anneeScolaire" class="form-select" required>
                                <option value="">Sélectionner</option>
                                @foreach($annees as $annee)
                                    <option value="{{ $annee->id_anneeScolaire }}" @selected($selectedAnnee == $annee->id_anneeScolaire)>
                                        {{ $annee->annee }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-bold">Leçons effectuées</label>
                        @foreach($modalLecons as $index => $lecon)
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <input type="text" name="lecons[{{ $index }}][titre]" class="form-control" placeholder="Titre de la leçon"
                                           value="{{ $lecon['titre'] ?? '' }}" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="lecons[{{ $index }}][nombre_heure]" class="form-control" min="0.25" max="24" step="0.25" placeholder="Heures"
                                           value="{{ $lecon['nombre_heure'] ?? '' }}" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" name="lecons[{{ $index }}][progression]" class="form-control" min="0" max="100" step="0.01" placeholder="Progression %"
                                           value="{{ $lecon['progression'] ?? 0 }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.presence-dynamic-form').forEach((form) => {
                const formData = JSON.parse(form.dataset.formData || '{}');
                const teacherSelect = form.querySelector('.js-enseignant-select');
                const classSelect = form.querySelector('.js-classe-select');
                const selectedTeacher = form.dataset.selectedTeacher || '';
                let selectedClass = form.dataset.selectedClass || '';

                function setClassOptions() {
                    const teacherId = teacherSelect.value || selectedTeacher;
                    const classes = formData.classesByTeacher?.[teacherId] || [];
                    classSelect.innerHTML = '<option value="">Sélectionner</option>';
                    classes.forEach((classe) => {
                        const option = document.createElement('option');
                        option.value = classe.id;
                        option.textContent = classe.label;
                        option.selected = String(classe.id) === String(selectedClass);
                        classSelect.appendChild(option);
                    });
                    classSelect.disabled = classes.length === 0;
                    selectedClass = '';
                }

                teacherSelect?.addEventListener('change', setClassOptions);
                if (selectedTeacher) {
                    teacherSelect.value = selectedTeacher;
                }
                setClassOptions();
            });
        });
    </script>
@endonce
