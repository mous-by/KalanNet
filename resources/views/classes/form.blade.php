@extends('layouts.app')

@section('content')
    @php
        $isEdit = $mode === 'edit';
        $action = $isEdit ? route('classes.update', $classe->id_classe) : route('classes.store');
        $oldMatieres = old('id_matiere');
        $oldEnseignants = old('id_enseignants', []);
        $oldCoefficients = old('coefficient', []);
        $initialRows = collect();

        if (is_array($oldMatieres)) {
            $initialRows = collect($oldMatieres)->map(function ($idMatiere, $index) use ($matieres, $oldEnseignants, $oldCoefficients) {
                return [
                    'id_matiere' => (int) $idMatiere,
                    'nom_matiere' => optional($matieres->firstWhere('id_matiere', (int) $idMatiere))->nom_matiere,
                    'id_enseignants' => $oldEnseignants[$index] ?? null,
                    'coefficient' => $oldCoefficients[$index] ?? 1,
                ];
            })->filter(fn ($row) => $row['nom_matiere']);
        } elseif ($isEdit) {
            $initialRows = $lignes->map(fn ($ligne) => [
                'id_matiere' => $ligne->id_matiere,
                'nom_matiere' => $ligne->matiere->nom_matiere ?? 'Matière supprimée',
                'id_enseignants' => $ligne->id_enseignants,
                'coefficient' => $ligne->coefficient ?? 1,
            ]);
        }

        $matieresJson = $matieres->map(fn ($matiere) => [
            'id' => $matiere->id_matiere,
            'nom' => $matiere->nom_matiere,
            'ordres' => $matiere->ordres->pluck('ordre_enseignement')->values(),
        ])->values();
        $enseignantsJson = $enseignants->map(fn ($enseignant) => [
            'id' => $enseignant->id_enseignant,
            'nom' => $enseignant->nom_prenom_enseignant,
        ])->values();
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Classes</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Liste des classes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Modification' : 'Enregistrement' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ $action }}" id="classe-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="card border-top border-4 border-primary shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ $isEdit ? 'Modifier la classe' : 'Nouvelle classe' }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nom_classe">Nom de la classe <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom_classe" name="nom_classe" value="{{ old('nom_classe', $classe->nom_classe) }}" placeholder="Ex: 7eme année A" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ordre_enseignement">Ordre d'enseignement <span class="text-danger">*</span></label>
                        <select class="form-select" id="ordre_enseignement" name="ordre_enseignement" required>
                            <option value="">Choisir...</option>
                            @foreach($ordres as $value => $label)
                                <option value="{{ $value }}" @selected(old('ordre_enseignement', $classe->ordreEnseignement) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-top border-4 border-primary shadow-sm">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">Matières associées</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0 border-start border-info border-4 py-2" id="matiere-order-help">
                    Choisissez l’ordre d’enseignement pour afficher automatiquement les matières correspondantes.
                </div>
                <div class="row justify-content-center mb-3">
                    <div class="col-md-7">
                        <label class="form-label" for="matiere_picker">Ajouter une matière</label>
                        <select class="form-select" id="matiere_picker">
                            <option value="">Choisir une matière</option>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id_matiere }}" data-ordres="{{ $matiere->ordres->pluck('ordre_enseignement')->implode('|') }}">{{ $matiere->nom_matiere }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Matière</th>
                                <th>Enseignant</th>
                                <th style="width: 140px;">Coefficient</th>
                                <th class="text-center" style="width: 90px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="table-matieres">
                            @foreach($initialRows as $row)
                                <tr data-matiere-id="{{ $row['id_matiere'] }}">
                                    <td>
                                        <span class="fw-bold">{{ $row['nom_matiere'] }}</span>
                                        <input type="hidden" name="id_matiere[]" value="{{ $row['id_matiere'] }}">
                                    </td>
                                    <td>
                                        <select class="form-select" name="id_enseignants[]">
                                            <option value="">Aucun</option>
                                            @foreach($enseignants as $enseignant)
                                                <option value="{{ $enseignant->id_enseignant }}" @selected((string) $row['id_enseignants'] === (string) $enseignant->id_enseignant)>
                                                    {{ $enseignant->nom_prenom_enseignant }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="coefficient[]" class="form-control" min="0" max="5" step="0.01" value="{{ $row['coefficient'] }}" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-light btn-sm remove-row" title="Supprimer">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('classes.index') }}" class="btn btn-light px-4">Retour</a>
                    <button type="submit" class="btn btn-primary px-4">{{ $isEdit ? 'Modifier' : 'Enregistrer' }}</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ordreMatiereMap = @json($ordreMatiereMap);
            const matieres = @json($matieresJson);
            const enseignants = @json($enseignantsJson);
            const picker = document.getElementById('matiere_picker');
            const tbody = document.getElementById('table-matieres');
            const nomClasse = document.getElementById('nom_classe');
            const ordreSelect = document.getElementById('ordre_enseignement');
            const help = document.getElementById('matiere-order-help');

            function enseignantOptions() {
                return '<option value="">Aucun</option>' + enseignants.map(function (enseignant) {
                    return '<option value="' + enseignant.id + '">' + escapeHtml(enseignant.nom) + '</option>';
                }).join('');
            }

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, function (char) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
                });
            }

            picker.addEventListener('change', function () {
                const id = Number(this.value);
                if (!id || tbody.querySelector('[data-matiere-id="' + id + '"]')) {
                    this.value = '';
                    return;
                }

                const matiere = matieres.find(function (item) { return Number(item.id) === id; });
                if (!matiere) return;

                tbody.insertAdjacentHTML('beforeend',
                    '<tr data-matiere-id="' + matiere.id + '">' +
                    '<td><span class="fw-bold">' + escapeHtml(matiere.nom) + '</span><input type="hidden" name="id_matiere[]" value="' + matiere.id + '"></td>' +
                    '<td><select class="form-select" name="id_enseignants[]">' + enseignantOptions() + '</select></td>' +
                    '<td><input type="number" name="coefficient[]" class="form-control" min="0" max="5" step="0.01" value="1" required></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-light btn-sm remove-row" title="Supprimer"><i class="bi bi-trash text-danger"></i></button></td>' +
                    '</tr>'
                );
                this.value = '';
            });

            function filterMatieresByOrder() {
                const selectedOrder = ordreSelect.value;
                const expectedOrder = ordreMatiereMap[selectedOrder] || '';
                Array.from(picker.options).forEach(function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    const ordres = (option.dataset.ordres || '').split('|').filter(Boolean);
                    option.hidden = expectedOrder && !ordres.includes(expectedOrder);
                });

                if (picker.value && picker.options[picker.selectedIndex]?.hidden) {
                    picker.value = '';
                }

                tbody.querySelectorAll('tr[data-matiere-id]').forEach(function (row) {
                    const matiere = matieres.find(item => Number(item.id) === Number(row.dataset.matiereId));
                    if (matiere && expectedOrder && !matiere.ordres.includes(expectedOrder)) {
                        row.remove();
                    }
                });

                help.textContent = expectedOrder
                    ? 'Matières affichées pour : ' + expectedOrder
                    : 'Choisissez l’ordre d’enseignement pour afficher automatiquement les matières correspondantes.';
            }

            ordreSelect.addEventListener('change', filterMatieresByOrder);
            filterMatieresByOrder();

            tbody.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-row');
                if (button) button.closest('tr').remove();
            });

            nomClasse.addEventListener('input', function () {
                const value = this.value.trim().toLowerCase();
                const niveaux = ['1er', '2eme', '3eme', '4eme', '5eme', '6eme', '7eme', '8eme', '9eme', '10eme', '11eme', '12eme'];
                if (niveaux.includes(value)) {
                    this.value = value + ' année';
                }
            });
        });
    </script>
@endpush
