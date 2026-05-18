@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Évaluations</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('evaluations.index') }}">Liste des évaluations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Préparer une évaluation</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('evaluations.store') }}" id="evaluation-form">
        @csrf
        <div class="card theme-card shadow-sm">
            <div class="card-header theme-header">
                <h5 class="mb-0 fw-bold">Préparer l'évaluation</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date_evaluation" class="form-control" value="{{ old('date_evaluation', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Libellé</label>
                        <input type="text" name="libeller" class="form-control" value="{{ old('libeller') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Heure début</label>
                        <input type="time" name="heure_debut" class="form-control" value="{{ old('heure_debut') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Heure fin</label>
                        <input type="time" name="heure_fin" class="form-control" value="{{ old('heure_fin') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Classe</label>
                        <select name="id_classe" id="id_classe" class="form-select" required>
                            <option value="">Choisir une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" data-ordre="{{ $classe->ordreEnseignement }}" @selected(old('id_classe') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Matière</label>
                        <select name="id_matiere" id="id_matiere" class="form-select" data-selected="{{ old('id_matiere') }}" required>
                            <option value="">Sélectionnez une matière</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Année scolaire</label>
                        <select name="id_annee_scolaire" id="id_annee_scolaire" class="form-select" required>
                            <option value="">Choisir une année</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected(old('id_annee_scolaire') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type note</label>
                        <select name="id_note" class="form-select" required>
                            <option value="">Choisir...</option>
                            @foreach($notes as $note)
                                <option value="{{ $note->id_note }}" @selected(old('id_note') == $note->id_note)>{{ $note->typeNote }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 trimestre-field">
                        <label class="form-label">Période</label>
                        <select name="id_trimestre" class="form-select">
                            <option value="">Choisir une période</option>
                            @foreach($trimestres as $trimestre)
                                <option value="{{ $trimestre->id_trimestre }}" @selected(old('id_trimestre') == $trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mois-field">
                        <label class="form-label">Mois</label>
                        <select name="mois" class="form-select">
                            <option value="">Sélectionner un mois</option>
                            @foreach($moisOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('mois') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('evaluations.index') }}" class="btn btn-light px-4">Retour</a>
                    <button type="submit" class="btn btn-primary px-4">Créer la fiche d'évaluation</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const classeSelect = document.getElementById('id_classe');
            const matiereSelect = document.getElementById('id_matiere');
            const selectedMatiere = matiereSelect.dataset.selected;
            const trimestreField = document.querySelector('.trimestre-field');
            const moisField = document.querySelector('.mois-field');

            function syncPeriodMode() {
                const ordre = classeSelect.options[classeSelect.selectedIndex]?.dataset.ordre || '';
                const isFondamentale1 = ordre === 'fondamentale1';
                trimestreField.style.display = isFondamentale1 ? 'none' : '';
                moisField.style.display = isFondamentale1 ? '' : 'none';
                trimestreField.querySelectorAll('select').forEach(select => select.disabled = isFondamentale1);
                moisField.querySelectorAll('select').forEach(select => select.disabled = !isFondamentale1);
            }

            function loadMatieres() {
                matiereSelect.innerHTML = '<option value="">Sélectionnez une matière</option>';
                if (!classeSelect.value) return;
                fetch("{{ url('/evaluations/classes') }}/" + classeSelect.value + "/matieres")
                    .then(response => response.json())
                    .then(data => (data.matiere || []).forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.id_matiere;
                        option.textContent = item.nom_matiere;
                        option.selected = String(item.id_matiere) === String(selectedMatiere);
                        matiereSelect.appendChild(option);
                    }));
            }

            classeSelect.addEventListener('change', function () {
                syncPeriodMode();
                loadMatieres();
            });
            syncPeriodMode();
            loadMatieres();
        });
    </script>
@endpush
