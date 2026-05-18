@extends('layouts.app')

@section('content')
    @php
        $first = $details->first();
        $maxNote = optional($first->noteType)->codeNote === 'NT10' ? 10 : 20;
        $moisOptions = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Évaluations</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('evaluations.index') }}">Liste des évaluations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Saisie des notes</li>
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

    <form method="POST" action="{{ route('evaluations.update', $evaluation->id_evaluation) }}">
        @csrf
        @method('PUT')

        <div class="card theme-card shadow-sm mb-4">
            <div class="card-header theme-header">
                <h5 class="mb-0 fw-bold">Fiche de l'évaluation</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" value="{{ $evaluation->date_evaluation }}" disabled>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Libellé</label>
                        <input type="text" class="form-control" value="{{ $evaluation->libeller }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Classe</label>
                        <input type="text" class="form-control" value="{{ $first->classe->nom_classe ?? '' }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Matière</label>
                        <input type="text" class="form-control" value="{{ $first->matiere->nom_matiere ?? '' }}" disabled>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Heure début</label>
                        <input type="time" class="form-control" value="{{ $evaluation->heure_debut }}" disabled>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Heure fin</label>
                        <input type="time" class="form-control" value="{{ $evaluation->heure_fin }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type note</label>
                        <input type="text" class="form-control" value="{{ $first->noteType->typeNote ?? '' }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Période</label>
                        <input type="text" class="form-control" value="{{ $first->mois ? ($moisOptions[(int) $first->mois] ?? $first->mois) : ($first->trimestre->nom_trimestre ?? '') }}" disabled>
                    </div>
                </div>
            </div>
        </div>

        <div class="card theme-card shadow-sm">
            <div class="card-header theme-header">
                <h5 class="mb-0 fw-bold">Saisie des notes</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>N° matricule</th>
                                <th>Élève</th>
                                <th style="width: 180px;">Note / {{ $maxNote }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($details as $index => $line)
                                <tr>
                                    <td>
                                        {{ $line->eleve->matricule ?? '' }}
                                        <input type="hidden" name="id_ligneEvaluation[]" value="{{ $line->id_ligneEvaluation }}">
                                    </td>
                                    <td class="fw-bold">{{ $line->eleve->nom_eleve ?? '' }} {{ $line->eleve->prenom_eleve ?? '' }}</td>
                                    <td>
                                        <input type="number" name="note[]" class="form-control" min="0" max="{{ $maxNote }}" step="0.01" value="{{ old('note.'.$index, $line->note ?? 0) }}" required>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('evaluations.index') }}" class="btn btn-light px-4">Retour</a>
                    <button type="submit" class="btn btn-primary px-4">Enregistrer les notes</button>
                </div>
            </div>
        </div>
    </form>
@endsection
