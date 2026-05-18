@extends('layouts.app')

@section('content')
    @php
        $isEdit = $mode === 'edit';
        $action = $isEdit ? route('programmes.update', $programme->id_programme) : route('programmes.store');
        $selectedClasse = old('id_classe_officielle', optional($programmeClasses->first())->id_classe);
        $oldMatieres = old('matieres');
        $rows = collect();

        if (is_array($oldMatieres)) {
            $rows = collect($oldMatieres)->values();
        } elseif ($isEdit) {
            $rows = $programmeClasses->map(fn ($pc) => [
                'id_matiere' => $pc->id_matiere,
                'lecons' => $pc->lecons->sortBy('numero')->map(fn ($lecon) => ['titre' => $lecon->titre])->values()->all(),
            ])->values();
        }
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Programmes</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('programmes.index') }}">Programmes officiels</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? 'Modification' : 'Création' }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ $action }}" id="programme-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="card border-top border-4 border-primary shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ $isEdit ? 'Modifier le programme officiel' : 'Nouveau programme officiel' }}</h5>
            </div>
            <div class="card-body">
                <label class="form-label">Programme officiel</label>
                <select name="id_classe_officielle" class="form-select" required>
                    <option value="">Choisir le programme officiel</option>
                    @foreach($classesOfficielles as $classeOfficielle)
                        <option value="{{ $classeOfficielle->id_classe_officielle }}" @selected($selectedClasse == $classeOfficielle->id_classe_officielle)>
                            Programme officiel {{ $classeOfficielle->nom_classe_officielle }} - {{ $classeOfficielle->ordre_enseignement }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card border-top border-4 border-primary shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">Matières et leçons</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" id="add-matiere">
                    <i class="bi bi-plus-lg me-1"></i>Ajouter une matière
                </button>
            </div>
            <div class="card-body">
                <div id="matieres-container">
                    @foreach($rows as $index => $row)
                        <div class="border rounded p-3 mb-3 matiere-row" data-index="{{ $index }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-10">
                                    <label class="form-label">Matière</label>
                                    <select name="matieres[{{ $index }}][id_matiere]" class="form-select" required>
                                        <option value="">Choisir une matière</option>
                                        @foreach($matieres as $matiere)
                                            <option value="{{ $matiere->id_matiere }}" @selected(($row['id_matiere'] ?? null) == $matiere->id_matiere)>{{ $matiere->nom_matiere }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-outline-danger remove-matiere"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <div class="lecons mt-3">
                                @foreach(($row['lecons'] ?? []) as $leconIndex => $lecon)
                                    <div class="input-group mb-2 lecon-row">
                                        <span class="input-group-text">{{ $leconIndex + 1 }}</span>
                                        <input type="text" name="matieres[{{ $index }}][lecons][{{ $leconIndex }}][titre]" value="{{ $lecon['titre'] ?? '' }}" class="form-control" placeholder="Titre de la leçon" required>
                                        <button type="button" class="btn btn-outline-danger remove-lecon"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary add-lecon">
                                <i class="bi bi-plus-lg me-1"></i>Ajouter une leçon
                            </button>
                        </div>
                    @endforeach
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('programmes.index') }}" class="btn btn-light px-4">Retour</a>
                    <button class="btn btn-primary px-4">Enregistrer le programme</button>
                </div>
            </div>
        </div>
    </form>

    <template id="matiere-template">
        <div class="border rounded p-3 mb-3 matiere-row" data-index="__INDEX__">
            <div class="row g-2 align-items-end">
                <div class="col-md-10">
                    <label class="form-label">Matière</label>
                    <select name="matieres[__INDEX__][id_matiere]" class="form-select" required>
                        <option value="">Choisir une matière</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id_matiere }}">{{ $matiere->nom_matiere }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-outline-danger remove-matiere"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="lecons mt-3"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary add-lecon">
                <i class="bi bi-plus-lg me-1"></i>Ajouter une leçon
            </button>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('matieres-container');
            const template = document.getElementById('matiere-template').innerHTML;
            let nextIndex = container.querySelectorAll('.matiere-row').length;

            function addLecon(row) {
                const index = row.dataset.index;
                const lecons = row.querySelector('.lecons');
                const leconIndex = lecons.querySelectorAll('.lecon-row').length;
                lecons.insertAdjacentHTML('beforeend',
                    '<div class="input-group mb-2 lecon-row">' +
                    '<span class="input-group-text">' + (leconIndex + 1) + '</span>' +
                    '<input type="text" name="matieres[' + index + '][lecons][' + leconIndex + '][titre]" class="form-control" placeholder="Titre de la leçon" required>' +
                    '<button type="button" class="btn btn-outline-danger remove-lecon"><i class="bi bi-x-lg"></i></button>' +
                    '</div>'
                );
            }

            document.getElementById('add-matiere').addEventListener('click', function () {
                container.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', nextIndex));
                addLecon(container.querySelector('.matiere-row[data-index="' + nextIndex + '"]'));
                nextIndex++;
            });

            container.addEventListener('click', function (event) {
                const add = event.target.closest('.add-lecon');
                if (add) addLecon(add.closest('.matiere-row'));

                const removeLecon = event.target.closest('.remove-lecon');
                if (removeLecon) removeLecon.closest('.lecon-row').remove();

                const removeMatiere = event.target.closest('.remove-matiere');
                if (removeMatiere) removeMatiere.closest('.matiere-row').remove();
            });

            if (!container.querySelector('.matiere-row')) {
                document.getElementById('add-matiere').click();
            }
        });
    </script>
@endsection
