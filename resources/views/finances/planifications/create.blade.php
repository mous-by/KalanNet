@extends('layouts.app')

@section('content')
@push('styles')
<style>
    .finance-menu .nav-link {
        color: var(--text-main);
        border-radius: 8px;
    }
    .finance-menu .nav-link.active {
        border-left: 4px solid var(--theme-primary);
        background: var(--accent-light);
        color: var(--theme-primary);
        font-weight: 700;
    }
    .finance-menu .menu-icon {
        width: 28px;
        height: 28px;
        background: var(--theme-primary);
        color: var(--text-on-accent);
    }
</style>
@endpush

<a href="javascript:history.back()" class="btn btn-primary mb-3">
    <i class="bi bi-arrow-left"></i>
</a>

<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Ajouter</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('finances.index') }}"><i class="bi bi-house"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Ajouter des Paiements</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('planification_errors'))
    <div class="alert alert-danger">
        @foreach(session('planification_errors') as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form action="{{ route('finances.planifications.store') }}" method="POST">
    @csrf
    <div class="row g-0">
        <div class="col-12 col-md-3">
            <div class="card theme-card h-100">
                <div class="card-header theme-header d-flex align-items-center">
                    <i class="bi bi-list me-2"></i> Menu
                </div>
                <div class="card-body p-2">
                    <ul class="nav flex-column gap-2 finance-menu">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center py-2" href="{{ route('finances.index') }}">
                                <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                    <i class="bi bi-graph-up"></i>
                                </span>
                                <span>Tableau de bord</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active d-flex align-items-center py-2" href="{{ route('finances.planifications') }}">
                                <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                    <i class="bi bi-calendar-check"></i>
                                </span>
                                <span>Planification</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center py-2" href="{{ route('finances.paiements') }}">
                                <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                    <i class="bi bi-cash-stack"></i>
                                </span>
                                <span>Paiements</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center py-2" href="{{ route('finances.paiements.historique') }}">
                                <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                    <i class="bi bi-clock-history"></i>
                                </span>
                                <span>Historique</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9 pt-4 pt-lg-0 p-md-3">
            <div class="card theme-card w-100">
                <div class="card-header theme-header">
                    <i class="bi bi-table me-1"></i>
                    Planification des paiements
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label" for="id_classe">Classe<span class="text-danger fs-6">*</span></label>
                            <select class="single-select form-select" id="id_classe" name="id_classe" required>
                                <option value="">Sélectionner une classe</option>
                                @foreach($classes as $classe)
                                    <option value="{{ $classe->id_classe }}" @selected(old('id_classe') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="id_annee">Année <span class="text-danger fs-6">*</span></label>
                            <select class="single-select form-select" id="id_annee" name="id_annee" required>
                                <option value="">Sélectionner une année</option>
                                @foreach($annees as $annee)
                                    <option value="{{ $annee->id_anneeScolaire }}" @selected(old('id_annee') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card theme-card w-100 mt-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" style="width:100%" id="dynamic-table">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">Motif</th>
                                    <th style="width: 20%;">Date de debut</th>
                                    <th style="width: 20%;">Date de fin</th>
                                    <th style="width: 20%;">Frais scolaire</th>
                                    <th class="text-center" style="width: 10%; font-size: 14px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tableListe_Planification">
                                <tr id="form-fields" class="form-row">
                                    <td>
                                        <select name="motif[]" class="form-select" required>
                                            <option value="trimestrielle">trimestrielle</option>
                                            <option value="mensuelle">mensuelle</option>
                                            <option value="annuelle">annuelle</option>
                                        </select>
                                    </td>
                                    <td><input type="date" class="form-control" name="date_debut[]" required></td>
                                    <td><input type="date" class="form-control" name="date_fin[]" required></td>
                                    <td><input type="number" class="form-control" name="montant[]" min="1" required></td>
                                    <td class="text-center align-middle">
                                        <div class="col-md-12 item-center">
                                            <button type="button" class="btn btn-primary mb-2" id="add-more" style="float: right;">
                                                <span style="font-size: 18px; line-height: 1;">+</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <button type="submit" name="envoie_re" class="btn btn-primary" style="float: right;">Envoyer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
$(document).on('click', '#add-more', function() {
    var newRow = $('#form-fields').clone();

    newRow.find('input').each(function() {
        $(this).val('');
    });

    newRow.find('#add-more').remove();

    newRow.find('td:last').html(`
        <div class="text-center align-middle">
            <button class="btn btn-primary remove" type="button">
                <span style="font-size: 16px; line-height: 1;">×</span>
            </button>
        </div>
    `);

    $('#dynamic-table tbody').append(newRow);
});

$(document).on('click', '.remove', function() {
    $(this).closest('tr').remove();
});
</script>
@endpush
