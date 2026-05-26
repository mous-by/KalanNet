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
    .classe-choice-list {
        max-height: 260px;
        overflow-y: auto;
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
                                <span>{{ $isPublicSchool ? 'Coopérative' : 'Planification' }}</span>
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
                    {{ $isPublicSchool ? 'Coopérative scolaire' : 'Planification des paiements' }}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                <label class="form-label mb-0">Classes<span class="text-danger fs-6">*</span></label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-classes">Tout sélectionner</button>
                            </div>
                            @php
                                $selectedClasses = collect(old('id_classes', []))->map(fn ($id) => (string) $id)->all();
                            @endphp
                            <div class="border rounded p-3 classe-choice-list">
                                <div class="row g-2">
                                    @foreach($classes as $classe)
                                        <div class="col-12 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input classe-checkbox" type="checkbox" name="id_classes[]" value="{{ $classe->id_classe }}" id="classe_{{ $classe->id_classe }}" @checked(in_array((string) $classe->id_classe, $selectedClasses, true))>
                                                <label class="form-check-label" for="classe_{{ $classe->id_classe }}">{{ $classe->nom_classe }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
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
                                    <th style="width: 30%;">{{ $isPublicSchool ? 'Type' : 'Motif' }}</th>
                                    <th style="width: 20%;">Date de debut</th>
                                    <th style="width: 20%;">Date de fin</th>
                                    <th style="width: 20%;">{{ $isPublicSchool ? 'Montant coopérative' : 'Frais scolaire' }}</th>
                                    <th class="text-center" style="width: 10%; font-size: 14px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="tableListe_Planification">
                                <tr id="form-fields" class="form-row">
                                    <td>
                                        <select name="motif[]" class="form-select planification-motif" required>
                                            @if($isPublicSchool)
                                                <option value="cooperative">Coopérative</option>
                                            @else
                                                <option value="mensuelle">mensuelle</option>
                                                <option value="trimestrielle">trimestrielle</option>
                                                <option value="annuelle">annuelle</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td><input type="date" class="form-control planification-date-debut" name="date_debut[]" required></td>
                                    <td><input type="date" class="form-control planification-date-fin" name="date_fin[]" required></td>
                                    <td><input type="number" class="form-control planification-montant" name="montant[]" min="1" required></td>
                                    <td class="text-center align-middle">
                                        <div class="col-md-12 item-center">
                                            <button type="button" class="btn btn-primary mb-2 {{ $isPublicSchool ? 'd-none' : '' }}" id="add-more" style="float: right;">
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
    const sourceRow = $('#dynamic-table tbody tr').last();
    updateMonthlyReference(sourceRow);

    var newRow = $('#form-fields').clone();
    const nextMotifValue = nextMotif(sourceRow.find('.planification-motif').val());
    const startValue = sourceRow.find('.planification-date-debut').val();

    newRow.find('input').each(function() {
        $(this).val('');
    });
    newRow.removeAttr('id');
    newRow.find('.planification-motif').each(function() {
        $(this).val(nextMotifValue);
        $(this).data('previous-motif', nextMotifValue);
    });
    newRow.find('.planification-date-debut').val(startValue);
    newRow.data('preferred-end-day', sourceRow.data('preferred-end-day'));

    newRow.find('#add-more').remove();

    newRow.find('td:last').html(`
        <div class="text-center align-middle">
            <button class="btn btn-primary remove" type="button">
                <span style="font-size: 16px; line-height: 1;">×</span>
            </button>
        </div>
    `);

    $('#dynamic-table tbody').append(newRow);
    refreshPlanificationEndDate(newRow);
    refreshPlanificationAmount(newRow, 'mensuelle');
});

$(document).on('click', '.remove', function() {
    $(this).closest('tr').remove();
});

function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function calculateEndDate(startValue, motif, preferredEndDay) {
    if (!startValue) {
        return '';
    }

    const months = motifMonths(motif);

    if (!months) {
        return '';
    }

    const start = new Date(`${startValue}T00:00:00`);
    if (motif === 'annuelle') {
        const endYear = start.getMonth() <= 5 ? start.getFullYear() : start.getFullYear() + 1;

        return `${endYear}-06-30`;
    }

    if (preferredEndDay) {
        const end = new Date(start.getFullYear(), start.getMonth() + months, 0);
        end.setDate(Math.min(preferredEndDay, end.getDate()));

        return formatDateForInput(end);
    }

    const end = new Date(start);
    end.setMonth(end.getMonth() + months);
    end.setDate(end.getDate() - 1);

    return formatDateForInput(end);
}

function motifMonths(motif) {
    const durations = {
        mensuelle: 1,
        trimestrielle: 3,
        annuelle: 9,
    };

    return durations[motif] || 0;
}

function nextMotif(currentMotif) {
    const order = ['mensuelle', 'trimestrielle', 'annuelle'];
    const currentIndex = order.indexOf(currentMotif);

    if (currentIndex === -1 || currentIndex === order.length - 1) {
        return order[0];
    }

    return order[currentIndex + 1];
}

function refreshPlanificationEndDate(row) {
    const motif = row.find('.planification-motif').val();
    const startValue = row.find('.planification-date-debut').val();
    const preferredEndDay = row.data('preferred-end-day');
    const endValue = calculateEndDate(startValue, motif, preferredEndDay);

    if (endValue) {
        row.find('.planification-date-fin').val(endValue);
    }
}

let monthlyAmountReference = null;

function numberFromInput(value) {
    const amount = parseFloat(value);

    return Number.isFinite(amount) && amount > 0 ? amount : null;
}

function updateMonthlyReference(row) {
    const motif = row.find('.planification-motif').val();
    const months = motifMonths(motif);
    const amount = numberFromInput(row.find('.planification-montant').val());

    if (amount && months) {
        monthlyAmountReference = amount / months;
        row.data('monthly-amount-reference', monthlyAmountReference);
    }
}

function refreshPlanificationAmount(row, previousMotif) {
    const motif = row.find('.planification-motif').val();
    const months = motifMonths(motif);
    const amountInput = row.find('.planification-montant');
    const currentAmount = numberFromInput(amountInput.val());
    const previousMonths = motifMonths(previousMotif);
    let monthlyAmount = row.data('monthly-amount-reference') || monthlyAmountReference;

    if (currentAmount && previousMonths) {
        monthlyAmount = currentAmount / previousMonths;
    }

    if (monthlyAmount && months) {
        amountInput.val(Math.round(monthlyAmount * months));
        monthlyAmountReference = monthlyAmount;
        row.data('monthly-amount-reference', monthlyAmount);
    }
}

$(document).on('change', '.planification-motif', function() {
    const row = $(this).closest('tr');
    const previousMotif = $(this).data('previous-motif') || $(this).val();
    const currentEndValue = row.find('.planification-date-fin').val();

    if (currentEndValue) {
        row.data('preferred-end-day', new Date(`${currentEndValue}T00:00:00`).getDate());
    }

    refreshPlanificationEndDate(row);
    refreshPlanificationAmount(row, previousMotif);
    $(this).data('previous-motif', $(this).val());
});

$(document).on('change', '.planification-date-debut', function() {
    refreshPlanificationEndDate($(this).closest('tr'));
});

$(document).on('change', '.planification-date-fin', function() {
    const endValue = $(this).val();

    if (endValue) {
        $(this).closest('tr').data('preferred-end-day', new Date(`${endValue}T00:00:00`).getDate());
    }
});

$(document).on('input change', '.planification-montant', function() {
    updateMonthlyReference($(this).closest('tr'));
});

$(document).on('click', '#toggle-classes', function() {
    const checkboxes = $('.classe-checkbox');
    const allChecked = checkboxes.length > 0 && checkboxes.filter(':checked').length === checkboxes.length;
    checkboxes.prop('checked', !allChecked);
    $(this).text(allChecked ? 'Tout sélectionner' : 'Tout désélectionner');
});

function updateToggleClassesLabel() {
    const checkboxes = $('.classe-checkbox');
    const allChecked = checkboxes.length > 0 && checkboxes.filter(':checked').length === checkboxes.length;
    $('#toggle-classes').text(allChecked ? 'Tout désélectionner' : 'Tout sélectionner');
}

$(document).on('change', '.classe-checkbox', updateToggleClassesLabel);
updateToggleClassesLabel();
$('.planification-motif').each(function() {
    $(this).data('previous-motif', $(this).val());
});
</script>
@endpush
