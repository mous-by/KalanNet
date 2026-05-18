@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Élèves</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('eleves.index') }}">Élèves</a></li>
                <li class="breadcrumb-item active">Inscription par groupe</li>
            </ol>
        </nav>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('inscriptions.group.store') }}">
    @csrf
    <div class="card theme-card mb-4">
        <div class="card-header">
            <h5 class="mb-0 fw-bold">Inscription par groupe</h5>
        </div>
        <div class="card-body row g-3">
            <div class="col-md-3">
                <label class="form-label">Classe</label>
                <select name="id_classe" class="form-select" required>
                    <option value="">Veuillez choisir</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id_classe }}">{{ $classe->nom_classe }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Année scolaire</label>
                <select name="id_annee" class="form-select" required>
                    <option value="">Veuillez choisir</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Planification</label>
                <select name="id_planification" class="form-select" required>
                    <option value="">Veuillez choisir</option>
                    @foreach($planifications as $planification)
                        <option value="{{ $planification->id_planification }}">
                            {{ $planification->motif }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date</label>
                <input type="date" name="date_inscription" class="form-control" value="{{ now()->toDateString() }}">
            </div>
        </div>
    </div>

    <div class="card theme-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="group-inscription-table">
                    <thead>
                        <tr>
                            <th>Prénom</th>
                            <th>Nom</th>
                            <th>Genre</th>
                            <th>Date naissance</th>
                            <th>Lieu naissance</th>
                            <th>Matricule</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="student-row">
                            <td><input name="eleves[0][prenom_eleve]" class="form-control" required></td>
                            <td><input name="eleves[0][nom_eleve]" class="form-control" required></td>
                            <td>
                                <select name="eleves[0][genre_eleve]" class="form-select" required>
                                    <option value="">Choisir</option>
                                    <option value="Masculin">Masculin</option>
                                    <option value="Féminin">Féminin</option>
                                </select>
                            </td>
                            <td><input type="date" name="eleves[0][date_naissance]" class="form-control"></td>
                            <td><input name="eleves[0][lieu_naiss]" class="form-control"></td>
                            <td><input name="eleves[0][matricule]" class="form-control" placeholder="Auto si vide"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-primary add-row"><span style="font-size:18px;line-height:1;">+</span></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">Valider les inscriptions</button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
let rowIndex = 1;
$(document).on('click', '.add-row', function () {
    const row = $('.student-row:first').clone();
    row.find('input').val('');
    row.find('select').val('');
    row.find('[name]').each(function () {
        this.name = this.name.replace(/eleves\[\d+\]/, 'eleves[' + rowIndex + ']');
    });
    row.find('td:last').html('<button type="button" class="btn btn-primary remove-row"><span style="font-size:16px;line-height:1;">×</span></button>');
    $('#group-inscription-table tbody').append(row);
    rowIndex++;
});
$(document).on('click', '.remove-row', function () {
    $(this).closest('tr').remove();
});
</script>
@endpush
@endsection
