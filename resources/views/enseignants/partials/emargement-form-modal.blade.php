@php
    $authUser = Auth::user();
    $isTeacher = $authUser->droit === 'enseignant';
    $selectedTeacher = old('id_enseignant', $emargement->id_enseignant ?? $authUser->id_enseignant);
    $selectedClass = old('id_classe', $emargement->id_classe ?? null);
    $selectedMatiere = old('id_matiere', $emargement->id_matiere ?? null);
    $selectedLecon = old('id_lecon', $emargement->id_lecon ?? null);
    $selectedAnnee = old('id_anneeScolaire', $emargement->id_anneeScolaire ?? $currentAcademicYearId);
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ $action }}" method="POST" class="emargement-dynamic-form"
                  data-form-data='@json($emargementFormData)'
                  data-selected-teacher="{{ $selectedTeacher }}"
                  data-selected-class="{{ $selectedClass }}"
                  data-selected-matiere="{{ $selectedMatiere }}"
                  data-selected-lecon="{{ $selectedLecon }}">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif
                <div class="modal-header theme-header">
                    <h5 class="modal-title fw-bold">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    @if(!$isTeacher)
                        <div class="d-flex justify-content-center align-items-center mb-3 flex-wrap gap-3">
                            @foreach(['CDI', 'CDD', 'VCT'] as $typeContrat)
                                <div class="form-check">
                                    <input type="radio" name="type_contrat_filter" value="{{ $typeContrat }}" class="form-check-input js-contract-filter" id="{{ $modalId }}_{{ $typeContrat }}">
                                    <label for="{{ $modalId }}_{{ $typeContrat }}" class="form-check-label">{{ $typeContrat }}</label>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Enseignant <span class="text-danger">*</span></label>
                            <select name="id_enseignant" class="form-select js-enseignant-select" required @disabled($isTeacher)>
                                <option value="">Sélectionnez un enseignant</option>
                                @foreach($enseignants as $enseignant)
                                    <option value="{{ $enseignant->id_enseignant }}" data-contrat="{{ $enseignant->type_contrat_enseignant }}" @selected($selectedTeacher == $enseignant->id_enseignant)>
                                        {{ $enseignant->nom_prenom_enseignant }}
                                    </option>
                                @endforeach
                            </select>
                            @if($isTeacher)
                                <input type="hidden" name="id_enseignant" value="{{ $authUser->id_enseignant }}">
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Classe <span class="text-danger">*</span></label>
                            <select name="id_classe" class="form-select js-classe-select" required>
                                <option value="">Sélectionnez une classe</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Matière <span class="text-danger">*</span></label>
                            <select name="id_matiere" class="form-select js-matiere-select" required>
                                <option value="">Sélectionnez une matière</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre d'heures <span class="text-danger">*</span></label>
                            <input type="number" name="nombre_heure" class="form-control" min="0.25" max="24" step="0.25"
                                   value="{{ old('nombre_heure', $emargement->nombre_heure ?? '') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Leçon <span class="text-danger">*</span></label>
                            <select name="id_lecon" class="form-select js-lecon-select" required>
                                <option value="">Sélectionnez une leçon</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trimestre <span class="text-danger">*</span></label>
                            <select name="id_trimestre" class="form-select" required>
                                <option value="">Sélectionner</option>
                                @foreach($trimestres as $trimestre)
                                    <option value="{{ $trimestre->id_trimestre }}" @selected(old('id_trimestre', $emargement->id_trimestre ?? null) == $trimestre->id_trimestre)>
                                        {{ $trimestre->nom_trimestre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Année scolaire <span class="text-danger">*</span></label>
                            <select name="id_anneeScolaire" class="form-select" required>
                                <option value="">Sélectionner</option>
                                @foreach($annees as $annee)
                                    <option value="{{ $annee->id_anneeScolaire }}" @selected($selectedAnnee == $annee->id_anneeScolaire)>
                                        {{ $annee->annee }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date et heure <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="date_emargement" class="form-control"
                                   value="{{ old('date_emargement', optional($emargement?->date_emargement)->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Chapitre</label>
                            <input type="text" name="chapitre" class="form-control" value="{{ old('chapitre', $emargement->chapitre ?? '') }}">
                        </div>
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
            document.querySelectorAll('.emargement-dynamic-form').forEach((form) => {
                const formData = JSON.parse(form.dataset.formData || '{}');
                const teacherSelect = form.querySelector('.js-enseignant-select');
                const classSelect = form.querySelector('.js-classe-select');
                const matiereSelect = form.querySelector('.js-matiere-select');
                const leconSelect = form.querySelector('.js-lecon-select');
                const contractRadios = Array.from(form.querySelectorAll('.js-contract-filter'));
                const allTeacherOptions = Array.from(teacherSelect.querySelectorAll('option[data-contrat]')).map((option) => option.cloneNode(true));
                const selected = {
                    teacher: form.dataset.selectedTeacher || '',
                    classe: form.dataset.selectedClass || '',
                    matiere: form.dataset.selectedMatiere || '',
                    lecon: form.dataset.selectedLecon || '',
                };

                function setOptions(select, items, placeholder, selectedValue) {
                    select.innerHTML = `<option value="">${placeholder}</option>`;
                    items.forEach((item) => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.label;
                        option.selected = String(item.id) === String(selectedValue);
                        select.appendChild(option);
                    });
                    select.disabled = items.length === 0;
                }

                function filterTeachers() {
                    if (!contractRadios.length) return;
                    const contract = form.querySelector('.js-contract-filter:checked')?.value || '';
                    const previousValue = teacherSelect.value || selected.teacher;
                    teacherSelect.innerHTML = '<option value="">Sélectionnez un enseignant</option>';
                    allTeacherOptions
                        .filter((option) => !contract || option.dataset.contrat === contract)
                        .forEach((option) => teacherSelect.appendChild(option.cloneNode(true)));
                    if ([...teacherSelect.options].some((option) => option.value === previousValue)) {
                        teacherSelect.value = previousValue;
                    }
                }

                function loadClasses() {
                    const teacherId = teacherSelect.value;
                    setOptions(classSelect, formData.classesByTeacher?.[teacherId] || [], 'Sélectionnez une classe', selected.classe);
                    selected.classe = '';
                    loadMatieres();
                }

                function loadMatieres() {
                    const key = `${teacherSelect.value}_${classSelect.value}`;
                    setOptions(matiereSelect, formData.matieresByTeacherClasse?.[key] || [], 'Sélectionnez une matière', selected.matiere);
                    selected.matiere = '';
                    loadLecons();
                }

                function loadLecons() {
                    const key = `${classSelect.value}_${matiereSelect.value}`;
                    setOptions(leconSelect, formData.leconsByClasseMatiere?.[key] || [], 'Sélectionnez une leçon', selected.lecon);
                    selected.lecon = '';
                }

                contractRadios.forEach((radio) => radio.addEventListener('change', function () {
                    filterTeachers();
                    loadClasses();
                }));
                teacherSelect.addEventListener('change', loadClasses);
                classSelect.addEventListener('change', loadMatieres);
                matiereSelect.addEventListener('change', loadLecons);

                filterTeachers();
                if (selected.teacher) {
                    teacherSelect.value = selected.teacher;
                }
                loadClasses();
            });
        });
    </script>
@endonce
