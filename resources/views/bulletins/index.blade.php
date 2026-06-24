@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <a href="{{ route('pedagogie.bulletins.classes') }}" class="btn btn-primary rounded-circle p-2 me-3" title="Retour">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="breadcrumb-title pe-3">Bulletins</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Classes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $classe->nom_classe }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold">Liste des bulletins - {{ $classe->nom_classe }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Mode de bulletin</label>
                    <select class="form-select" id="periode_mode">
                        <option value="trimestre">Trimestriel</option>
                        <option value="mois">Composition mensuelle</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Année scolaire</label>
                    <select class="form-select" id="id_annee">
                        <option value="">Sélectionner une année</option>
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 trimestre-field">
                    <label class="form-label">Période</label>
                    <select class="form-select" id="id_trimestre">
                        <option value="">Sélectionner une période</option>
                        @foreach($trimestres as $trimestre)
                            <option value="{{ $trimestre->id_trimestre }}">{{ $trimestre->nom_trimestre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mois-field">
                    <label class="form-label">Mois</label>
                    <select class="form-select" id="mois">
                        <option value="">Sélectionner un mois</option>
                        @foreach($moisOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold">Bulletins disponibles</h5>
                <small class="opacity-75">Cochez les élèves à imprimer ou lancez toute la classe.</small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-light text-dark border" id="selected-bulletins-count">0 sélection</span>
                <button type="button" class="btn theme-action-btn px-4" id="print-selected-bulletins" disabled>
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    <i class="bi bi-printer me-2"></i><span class="btn-label">Imprimer la sélection</span>
                </button>
                <button type="button" class="btn theme-outline-btn px-4" id="print-all-bulletins" disabled>
                    <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                    <i class="bi bi-files me-2"></i><span class="btn-label">Toute la classe</span>
                </button>
                @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('bulletins_publication'))
                    <button type="button" class="btn btn-success px-4" id="publish-bulletins" disabled>
                        <i class="bi bi-check2-circle me-2"></i>Publier
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-4" id="unpublish-bulletins" disabled>
                        <i class="bi bi-eye-slash me-2"></i>Retirer
                    </button>
                @endif
            </div>
        </div>
        <div class="card-body">
            <form id="publish-bulletins-form" method="POST" action="{{ route('pedagogie.bulletins.publish', $classe->id_classe) }}" class="d-none">
                @csrf
                <input type="hidden" name="id_annee" class="publish-id-annee">
                <input type="hidden" name="id_trimestre" class="publish-id-trimestre">
                <input type="hidden" name="mois" class="publish-mois">
            </form>
            <form id="unpublish-bulletins-form" method="POST" action="{{ route('pedagogie.bulletins.unpublish', $classe->id_classe) }}" class="d-none">
                @csrf
                @method('DELETE')
                <input type="hidden" name="id_annee" class="publish-id-annee">
                <input type="hidden" name="id_trimestre" class="publish-id-trimestre">
                <input type="hidden" name="mois" class="publish-mois">
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 48px;">
                                <input type="checkbox" class="form-check-input" id="select-all-bulletins" aria-label="Tout sélectionner" disabled>
                            </th>
                            <th>Matricule</th>
                            <th>Nom & Prénom</th>
                            <th>Genre</th>
                            <th>Moyenne</th>
                            <th>Rang</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="bulletins-body">
                        <tr><td colspan="7" class="text-center py-4 text-muted">Sélectionnez l'année scolaire et la période.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ordre = @json($classe->ordreEnseignement);
            const periodeMode = document.getElementById('periode_mode');
            const annee = document.getElementById('id_annee');
            const trimestre = document.getElementById('id_trimestre');
            const mois = document.getElementById('mois');
            const body = document.getElementById('bulletins-body');
            const trimestreField = document.querySelector('.trimestre-field');
            const moisField = document.querySelector('.mois-field');
            const selectAll = document.getElementById('select-all-bulletins');
            const printSelected = document.getElementById('print-selected-bulletins');
            const printAll = document.getElementById('print-all-bulletins');
            const selectedCount = document.getElementById('selected-bulletins-count');
            const publishButton = document.getElementById('publish-bulletins');
            const unpublishButton = document.getElementById('unpublish-bulletins');
            const publishForm = document.getElementById('publish-bulletins-form');
            const unpublishForm = document.getElementById('unpublish-bulletins-form');

            if (ordre === 'fondamentale1') {
                periodeMode.value = 'mois';
            }
            updatePeriodFields();

            function rangLabel(row) {
                const suffix = row.rang === 1 ? '1er' : row.rang + 'e';
                return row.exaequo ? suffix + ' ex aequo' : suffix;
            }

            function loadBulletins() {
                const idAnnee = annee.value;
                const mode = periodeMode.value;
                const idTrimestre = trimestre.value;
                const moisValue = mois.value;
                resetBulkActions();
                syncPublicationButtons(false);
                if (!idAnnee || (mode === 'mois' ? !moisValue : !idTrimestre)) {
                    body.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">Sélectionnez l’année scolaire et la période.</td></tr>';
                    return;
                }

                const params = new URLSearchParams({id_annee: idAnnee});
                if (mode === 'mois') params.set('mois', moisValue);
                else params.set('id_trimestre', idTrimestre);

                body.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Chargement des bulletins...</td></tr>';
                fetch("{{ route('pedagogie.bulletins.data', $classe->id_classe) }}?" + params.toString())
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Impossible de charger les bulletins.');
                        }
                        return response.json();
                    })
                    .then(rows => {
                        body.innerHTML = rows.map(row => {
                            const printParams = new URLSearchParams({id_annee: idAnnee});
                            if (mode === 'mois') printParams.set('mois', moisValue);
                            else printParams.set('id_trimestre', idTrimestre);
                            return '<tr>' +
                                '<td class="text-center"><input type="checkbox" class="form-check-input bulletin-checkbox" value="' + row.id_eleve + '" aria-label="Sélectionner ce bulletin"></td>' +
                                '<td>' + escapeHtml(row.matricule || '') + '</td>' +
                                '<td class="fw-bold">' + escapeHtml((row.nom_eleve || '') + ' ' + (row.prenom_eleve || '')) + '</td>' +
                                '<td>' + escapeHtml(row.genre_eleve || '') + '</td>' +
                                '<td>' + Number(row.moyenne || 0).toFixed(2) + '</td>' +
                                '<td>' + rangLabel(row) + '</td>' +
                                '<td class="text-center"><a class="btn btn-light btn-sm p-2" target="_blank" href="{{ url('/pedagogie/bulletins') }}/' + row.id_eleve + '/download?' + printParams.toString() + '"><i class="bi bi-printer text-primary"></i></a></td>' +
                            '</tr>';
                        }).join('') || '<tr><td colspan="7" class="text-center py-4 text-muted">Aucun bulletin trouvé pour cette période.</td></tr>';
                        printAll.disabled = rows.length === 0;
                        selectAll.disabled = rows.length === 0;
                        syncPublicationButtons(rows.length > 0);
                        bindSelection();
                    })
                    .catch(() => {
                        body.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Impossible de charger les bulletins. Vérifiez que les notes sont saisies pour cette année et cette période.</td></tr>';
                        resetBulkActions();
                        syncPublicationButtons(false);
                    });
            }

            function bindSelection() {
                document.querySelectorAll('.bulletin-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', updateSelectionState);
                });
                updateSelectionState();
            }

            function selectedIds() {
                return Array.from(document.querySelectorAll('.bulletin-checkbox:checked')).map(checkbox => checkbox.value);
            }

            function updateSelectionState() {
                const checkboxes = Array.from(document.querySelectorAll('.bulletin-checkbox'));
                const checked = selectedIds();
                selectedCount.textContent = checked.length + ' sélection' + (checked.length > 1 ? 's' : '');
                printSelected.disabled = checked.length === 0;
                selectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
            }

            function resetBulkActions() {
                selectAll.checked = false;
                selectAll.indeterminate = false;
                selectAll.disabled = true;
                printSelected.disabled = true;
                printAll.disabled = true;
                selectedCount.textContent = '0 sélection';
            }

            function syncPublicationButtons(enabled) {
                if (publishButton) publishButton.disabled = !enabled;
                if (unpublishButton) unpublishButton.disabled = !enabled;
            }

            function fillPublicationForm(form) {
                form.querySelectorAll('.publish-id-annee').forEach(input => input.value = annee.value);
                form.querySelectorAll('.publish-id-trimestre').forEach(input => input.value = periodeMode.value === 'mois' ? '' : trimestre.value);
                form.querySelectorAll('.publish-mois').forEach(input => input.value = periodeMode.value === 'mois' ? mois.value : '');
            }

            function submitBulk(ids) {
                const params = new URLSearchParams({ id_annee: annee.value });
                if (periodeMode.value === 'mois') params.set('mois', mois.value);
                else params.set('id_trimestre', trimestre.value);
                if (ids && ids.length > 0) params.set('ids', JSON.stringify(ids));
                const url = "{{ route('pedagogie.bulletins.generator', $classe->id_classe) }}?" + params.toString();
                window.open(url, 'kn-bulletin-gen', 'width=1200,height=760,resizable=yes,scrollbars=yes');
            }

            function updatePeriodFields() {
                const mode = periodeMode.value;
                trimestreField.style.display = mode === 'mois' ? 'none' : '';
                moisField.style.display = mode === 'mois' ? '' : 'none';
                if (mode === 'mois') trimestre.value = '';
                else mois.value = '';
                loadBulletins();
            }


            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[c]));
            }

            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.bulletin-checkbox').forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                updateSelectionState();
            });
            printSelected.addEventListener('click', () => submitBulk(selectedIds()));
            printAll.addEventListener('click', () => submitBulk([]));
            if (publishButton && publishForm) {
                publishButton.addEventListener('click', () => {
                    fillPublicationForm(publishForm);
                    publishForm.submit();
                });
            }
            if (unpublishButton && unpublishForm) {
                unpublishButton.addEventListener('click', () => {
                    fillPublicationForm(unpublishForm);
                    unpublishForm.submit();
                });
            }
            periodeMode.addEventListener('change', updatePeriodFields);
            annee.addEventListener('change', loadBulletins);
            trimestre.addEventListener('change', loadBulletins);
            mois.addEventListener('change', loadBulletins);
        });
    </script>
@endpush

@push('styles')
    <style>
        html[data-theme] .theme-action-btn {
            background-color: var(--theme-accent) !important;
            border-color: var(--theme-accent) !important;
            color: #fff !important;
        }
        html[data-theme] .theme-outline-btn {
            background: var(--accent-light) !important;
            border-color: var(--border-color) !important;
            color: var(--theme-accent) !important;
        }
    </style>
@endpush
