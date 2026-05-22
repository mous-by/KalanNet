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
    $durationOptions = [
        '0.1667' => '10 minutes',
        '0.25' => '15 minutes',
        '0.50' => '30 minutes',
        '0.75' => '45 minutes',
        '1.00' => '1 heure',
        '1.25' => '1h15',
        '1.50' => '1h30',
        '1.75' => '1h45',
        '2.00' => '2 heures',
        '3.00' => '3 heures',
        '4.00' => '4 heures',
    ];
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
                            <input type="number" name="nombre_heure" class="form-control js-total-hours" min="0.1667" max="24" step="0.0001"
                                   value="{{ old('nombre_heure', $presence->nombre_heure ?? '') }}" required readonly>
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
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                            <label class="form-label fw-bold mb-0">Leçons effectuées</label>
                            <button type="button" class="btn btn-sm theme-pill-active js-add-lecon">
                                <i class="bx bx-plus me-1"></i>Ajouter une leçon
                            </button>
                        </div>
                        <div class="js-lecons-container">
                            @foreach($modalLecons as $index => $lecon)
                                <div class="row g-2 mb-2 align-items-end js-lecon-row">
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted">Titre</label>
                                        <input type="text" name="lecons[{{ $index }}][titre]" class="form-control" placeholder="Titre de la leçon"
                                               value="{{ $lecon['titre'] ?? '' }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Durée</label>
                                        <select name="lecons[{{ $index }}][nombre_heure]" class="form-select js-lecon-hours" required>
                                            <option value="">Sélectionner</option>
                                            @foreach($durationOptions as $value => $label)
                                                <option value="{{ $value }}" @selected((string) ($lecon['nombre_heure'] ?? '') === (string) $value || number_format((float) ($lecon['nombre_heure'] ?? 0), 2, '.', '') === number_format((float) $value, 2, '.', ''))>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Progression</label>
                                        <div class="input-group">
                                            <input type="number" name="lecons[{{ $index }}][progression]" class="form-control" min="0" max="100" step="0.01" placeholder="0-100"
                                                   value="{{ $lecon['progression'] ?? 0 }}">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-1 d-grid">
                                        <button type="button" class="btn btn-outline-danger js-remove-lecon" title="Retirer la leçon">
                                            <i class="bx bx-x"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="small text-muted mt-2">Le total d'heures est recalculé automatiquement à partir des durées des leçons.</div>
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
            const durationOptions = @json($durationOptions);

            document.querySelectorAll('.presence-dynamic-form').forEach((form) => {
                const formData = JSON.parse(form.dataset.formData || '{}');
                const teacherSelect = form.querySelector('.js-enseignant-select');
                const classSelect = form.querySelector('.js-classe-select');
                const container = form.querySelector('.js-lecons-container');
                const addButton = form.querySelector('.js-add-lecon');
                const totalHoursInput = form.querySelector('.js-total-hours');
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

                function durationSelectHtml(index) {
                    const options = Object.entries(durationOptions)
                        .map(([value, label]) => `<option value="${value}">${label}</option>`)
                        .join('');

                    return `<select name="lecons[${index}][nombre_heure]" class="form-select js-lecon-hours" required><option value="">Sélectionner</option>${options}</select>`;
                }

                function leconRowHtml(index) {
                    return `
                        <div class="row g-2 mb-2 align-items-end js-lecon-row">
                            <div class="col-md-5">
                                <label class="form-label small text-muted">Titre</label>
                                <input type="text" name="lecons[${index}][titre]" class="form-control" placeholder="Titre de la leçon" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Durée</label>
                                ${durationSelectHtml(index)}
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted">Progression</label>
                                <div class="input-group">
                                    <input type="number" name="lecons[${index}][progression]" class="form-control" min="0" max="100" step="0.01" placeholder="0-100" value="0">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-1 d-grid">
                                <button type="button" class="btn btn-outline-danger js-remove-lecon" title="Retirer la leçon">
                                    <i class="bx bx-x"></i>
                                </button>
                            </div>
                        </div>`;
                }

                function reindexRows() {
                    container?.querySelectorAll('.js-lecon-row').forEach((row, index) => {
                        row.querySelectorAll('input, select').forEach((field) => {
                            field.name = field.name.replace(/lecons\[\d+\]/, `lecons[${index}]`);
                        });
                    });
                }

                function toggleRemoveButtons() {
                    const rows = container?.querySelectorAll('.js-lecon-row') || [];
                    rows.forEach((row) => {
                        const button = row.querySelector('.js-remove-lecon');
                        if (button) button.disabled = rows.length <= 1;
                    });
                }

                function recalculateTotalHours() {
                    const total = Array.from(form.querySelectorAll('.js-lecon-hours'))
                        .reduce((sum, select) => sum + (parseFloat(select.value || '0') || 0), 0);

                    if (totalHoursInput) {
                        totalHoursInput.value = total > 0 ? total.toFixed(4) : '';
                    }
                }

                teacherSelect?.addEventListener('change', setClassOptions);
                addButton?.addEventListener('click', function () {
                    const index = container.querySelectorAll('.js-lecon-row').length;
                    container.insertAdjacentHTML('beforeend', leconRowHtml(index));
                    toggleRemoveButtons();
                    recalculateTotalHours();
                });

                container?.addEventListener('click', function (event) {
                    const button = event.target.closest('.js-remove-lecon');
                    if (!button) return;

                    const rows = container.querySelectorAll('.js-lecon-row');
                    if (rows.length <= 1) return;

                    button.closest('.js-lecon-row').remove();
                    reindexRows();
                    toggleRemoveButtons();
                    recalculateTotalHours();
                });

                container?.addEventListener('change', function (event) {
                    if (event.target.classList.contains('js-lecon-hours')) {
                        recalculateTotalHours();
                    }
                });

                if (selectedTeacher) {
                    teacherSelect.value = selectedTeacher;
                }
                setClassOptions();
                toggleRemoveButtons();
                recalculateTotalHours();
            });
        });
    </script>
@endonce
