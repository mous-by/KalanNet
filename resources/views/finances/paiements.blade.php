@extends('layouts.app')

@section('content')
@php
    $canPay = auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('paiements_faire');
    $selectedType = $filters['type_planification'] ?? '';
    $selectedTrimestre = $filters['id_trimestre'] ?? optional($trimestres->first())->id_trimestre;
    $paymentDate = $filters['date_paiement'] ?? now()->toDateString();
@endphp

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
    .finance-tabs .nav-link {
        color: var(--theme-primary);
        border-color: var(--border-color);
        font-weight: 600;
    }
    .finance-tabs .nav-link.active {
        background: var(--theme-primary) !important;
        color: var(--text-on-accent) !important;
        border-color: var(--theme-primary) !important;
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
                <li class="breadcrumb-item active" aria-current="page">Liste des Paiements</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
        <div class="alert alert-success">
        <div>{{ session('success') }}</div>
        @if(session('created_payment_ids'))
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach(session('created_payment_ids') as $paymentId)
                    <a href="{{ route('finances.paiements.download', $paymentId) }}" class="btn btn-sm btn-outline-success">
                        Reçu #{{ $paymentId }}
                    </a>
                    <a href="{{ route('finances.paiements.thermique', $paymentId) }}" class="btn btn-sm btn-outline-success">
                        Thermique #{{ $paymentId }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('payment_errors'))
    <div class="alert alert-warning">
        <strong>Lignes ignorées :</strong>
        <ul class="mb-0">
            @foreach(session('payment_errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

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
                        <a class="nav-link d-flex align-items-center py-2" href="{{ route('finances.planifications') }}">
                            <span class="menu-icon rounded-circle d-flex align-items-center justify-content-center me-2">
                                <i class="bi bi-calendar-check"></i>
                            </span>
                            <span>{{ $isPublicSchool ? 'Coopérative' : 'Planification' }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active d-flex align-items-center py-2" href="{{ route('finances.paiements') }}">
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

    <div class="col-12 col-md-9 pt-4 pt-md-0 p-md-3">
        <div class="card theme-card w-100">
            <div class="card-header theme-header">
                <i class="bi bi-cash-coin me-1"></i>
                {{ $isPublicSchool ? 'Paiements coopérative' : 'Paiements scolaires' }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('finances.paiements.filter') }}" class="row g-3" id="paymentFilterForm" data-auto-filter="true">
                    @csrf
                    <div class="col-12">
                        <p class="mb-1">Filtré par</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_classe">Classe<span class="text-danger fs-6">*</span></label>
                        <select class="single-select form-select auto-submit-payment" id="id_classe" name="id_classe" required>
                            <option value="">Sélectionner une classe</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" @selected(($filters['id_classe'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_annee">Année <span class="text-danger fs-6">*</span></label>
                        <select class="single-select form-select auto-submit-payment" id="id_annee" name="id_annee" required>
                            <option value="">Sélectionner une année</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_trimestre">Periode<span class="text-danger fs-6">*</span></label>
                        <select class="single-select form-select auto-submit-payment" id="id_trimestre" name="id_trimestre" required>
                            <option value="">Sélectionner une periode</option>
                            @foreach($trimestres as $trimestre)
                                <option value="{{ $trimestre->id_trimestre }}" @selected($selectedTrimestre == $trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="date_paiement">Date<span class="text-danger fs-6">*</span></label>
                        <input class="form-control" type="date" required name="date_paiement" id="date_paiement" value="{{ $paymentDate }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="reference">Référence <span class="text-danger fs-6">*</span></label>
                        <input type="text" class="form-control" id="reference" value="{{ $newRef }}" readonly>
                    </div>
                    <div class="col-md-8 d-flex align-items-end justify-content-end">
                        <input type="hidden" name="type_planification" value="{{ $selectedType }}">
                        <button type="submit" class="btn btn-primary d-none">
                            <i class="bi bi-search me-1"></i>Afficher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('finances.paiements.groupes.store') }}" id="formValidationExamples" novalidate>
    @csrf
    <input type="hidden" name="id_classe" value="{{ $filters['id_classe'] ?? '' }}">
    <input type="hidden" name="id_annee" value="{{ $filters['id_annee'] ?? '' }}">
    <input type="hidden" name="id_trimestre" value="{{ $selectedTrimestre }}">
    <input type="hidden" name="date_paiement" value="{{ $paymentDate }}">
    <input type="hidden" name="type_planification" value="{{ $selectedType }}">

    <div class="card theme-card w-100 mt-4">
        <div class="card-body">
            <ul class="nav nav-tabs finance-tabs mb-3" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === '') active @endif" href="#" data-filter-type="">Tous</a>
                    </li>
                    @if($isPublicSchool)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'cooperative') active @endif" href="#" data-filter-type="cooperative">Coopérative</a>
                    </li>
                    @else
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'trimestriel') active @endif" href="#" data-filter-type="trimestriel">Paiement Trimestriel</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'mensuel') active @endif" href="#" data-filter-type="mensuel">Paiement Mensuel</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'annuel') active @endif" href="#" data-filter-type="annuel">Paiement Annuel</a>
                    </li>
                    @endif
            </ul>

            @if(!$caisse)
                <div class="alert alert-danger">Vous devez activer une caisse avant tout encaissement.</div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-bordered w-100 mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 5%; font-size: 14px;">
                                <div class="form-check form-check-primary d-flex justify-content-center">
                                    <input type="checkbox" id="checkAll" class="form-check-input">
                                </div>
                            </th>
                            <th>N°</th>
                            <th>Eleve</th>
                            <th>{{ $isPublicSchool ? 'Coopérative' : 'Motif' }}</th>
                            <th>Parent</th>
                            <th>Téléphone</th>
                            <th>Montant Total</th>
                            <th>Montant reste</th>
                            <th>Montant à payer</th>
                        </tr>
                    </thead>
                    <tbody id="resultat_periode_tableau">
                        @forelse($paymentRows as $index => $row)
                            <tr class="{{ $row->row_class }}">
                                <td class="text-center">
                                    <input type="checkbox" name="alert[]" value="{{ $row->eleve->id_eleve }}" class="form-check-input row-check checkItem">
                                </td>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <input type="hidden" name="id_eleve[]" value="{{ $row->eleve->id_eleve }}">
                                    <input type="hidden" name="id_planification[]" value="{{ $row->planification->id_planification }}">
                                    {{ $row->eleve->prenom_eleve }} {{ $row->eleve->nom_eleve }}
                                </td>
                                <td>
                                    <input type="text" name="motif[]" value="{{ $isPublicSchool ? 'Coopérative' : $row->planification->motif }}" class="form-control form-control-sm">
                                </td>
                                <td style="min-width: 220px;">
                                    <select name="parent_id[]" class="form-select form-select-sm payer-select">
                                        <option value="">Choisir</option>
                                        @foreach($row->parents as $parent)
                                            <option value="{{ $parent->id_parent }}" data-phone="{{ $parent->telephone_parent }}">
                                                {{ $parent->nom_prenom_parent }}
                                            </option>
                                        @endforeach
                                        <option value="autre">Autre personne</option>
                                    </select>
                                    <input type="text" name="autre_personne_nom[]" class="form-control form-control-sm mt-2 other-name d-none" placeholder="Nom du payeur">
                                </td>
                                <td style="min-width: 170px;">
                                    <input type="text" class="form-control form-control-sm payer-phone" readonly>
                                    <input type="text" name="autre_personne_telephone[]" class="form-control form-control-sm mt-2 other-phone d-none autre-tel" placeholder="Téléphone">
                                </td>
                                <td>{{ number_format($row->montant_total, 0, ',', ' ') }} F CFA</td>
                                <td class="reste-cell" data-original-reste="{{ $row->reste_a_payer }}">
                                    <span class="current-reste">{{ number_format($row->reste_a_payer, 0, ',', ' ') }} F CFA</span>
                                </td>
                                <td>
                                    <input type="number" name="montant_recu[]" class="form-control form-control-sm montant_recu" min="1" max="{{ $row->reste_a_payer }}" value="{{ $row->reste_a_payer }}">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">{{ $isPublicSchool ? 'Aucun élève à afficher pour la coopérative.' : 'Aucun élève à afficher pour ce type de planification.' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2 d-flex justify-content-between flex-wrap gap-2">
                <button type="button" id="btnPrintPaiements" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i>Imprimer la liste des paiements
                </button>
                <button type="submit" id="btn-valider-paiement" name="envoie_re" class="btn btn-primary" @disabled(!$canPay || !$caisse || $paymentRows->isEmpty())>
                    valide les paiements
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script src="{{ asset('assets/mon_js/html2pdf.bundle.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            document.querySelectorAll('.row-check').forEach(function (checkbox) {
                checkbox.checked = checkAll.checked;
            });
        });
    }

    document.querySelectorAll('.payer-select').forEach(function (select) {
        select.addEventListener('change', function () {
            const row = select.closest('tr');
            const selected = select.options[select.selectedIndex];
            const isOther = select.value === 'autre';
            row.querySelector('.payer-phone').value = isOther ? '' : (selected.dataset.phone || '');
            row.querySelector('.other-name').classList.toggle('d-none', !isOther);
            row.querySelector('.other-phone').classList.toggle('d-none', !isOther);
        });
    });

    const printButton = document.getElementById('btnPrintPaiements');
    if (printButton) {
        printButton.addEventListener('click', function () {
            const tableDiv = document.querySelector('.table-responsive');
            if (!tableDiv) return;

            const clone = tableDiv.cloneNode(true);
            
            // Remove checkboxes column and actions
            clone.querySelectorAll('th:first-child, td:first-child').forEach(el => el.remove());
            
            // Replace selects with text of selected option
            clone.querySelectorAll('select').forEach(select => {
                const text = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : '';
                const span = document.createElement('span');
                span.innerText = text === 'Choisir' ? '' : text;
                select.parentNode.replaceChild(span, select);
            });
            
            // Replace input values with spans
            clone.querySelectorAll('input').forEach(input => {
                const span = document.createElement('span');
                span.innerText = input.value;
                input.parentNode.replaceChild(span, input);
            });

            // Create custom container with professional header
            const container = document.createElement('div');
            container.className = 'p-4';
            
            const classSelect = document.getElementById('id_classe');
            const className = classSelect ? classSelect.options[classSelect.selectedIndex]?.text || '' : '';
            const anneeSelect = document.getElementById('id_annee');
            const anneeName = anneeSelect ? anneeSelect.options[anneeSelect.selectedIndex]?.text || '' : '';
            const trimSelect = document.getElementById('id_trimestre');
            const trimName = trimSelect ? trimSelect.options[trimSelect.selectedIndex]?.text || '' : '';

            container.innerHTML = `
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-uppercase mb-1" style="color: #333;">Fiche de Paiement des Élèves</h3>
                    <div class="fs-6 text-muted mb-2">${className} | Année Scolaire: ${anneeName} | Trimestre: ${trimName}</div>
                    <div style="border-bottom: 2px solid #0d6efd; width: 80px; margin: 0 auto;"></div>
                </div>
            `;
            container.appendChild(clone);

            html2pdf()
                .set({
                    margin: [10, 10, 15, 10],
                    filename: 'liste_paiements_' + className.replace(/\s+/g, '_') + '.pdf',
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
                })
                .from(container)
                .save();
        });
    }

    // Dynamic remaining recalculation
    document.querySelectorAll('.montant_recu').forEach(function (input) {
        // Initial recalculation in case input value is different
        const recalculate = function() {
            const row = input.closest('tr');
            const resteCell = row.querySelector('.reste-cell');
            if (!resteCell) return;
            
            const originalReste = parseFloat(resteCell.dataset.originalReste) || 0;
            const typedAmount = parseFloat(input.value) || 0;
            const newReste = Math.max(0, originalReste - typedAmount);
            
            const formatted = new Intl.NumberFormat('fr-FR').format(newReste) + ' F CFA';
            const span = resteCell.querySelector('.current-reste');
            if (span) {
                span.innerText = formatted;
                if (newReste === 0) {
                    span.className = 'current-reste badge bg-success text-white px-2 py-1';
                } else {
                    span.className = 'current-reste';
                }
            }
        };

        input.addEventListener('input', recalculate);
    });

    const filterForm = document.getElementById('paymentFilterForm');
    if (filterForm) {
        document.querySelectorAll('.auto-submit-payment').forEach(function (field) {
            field.addEventListener('change', function () {
                const classe = document.getElementById('id_classe')?.value;
                const annee = document.getElementById('id_annee')?.value;
                if (classe && annee) {
                    filterForm.submit();
                }
            });
        });

        document.querySelectorAll('[data-filter-type]').forEach(function (tabLink) {
            tabLink.addEventListener('click', function (event) {
                event.preventDefault();
                const type = this.dataset.filterType || '';
                const typeInput = filterForm.querySelector('input[name="type_planification"]');

                if (typeInput) {
                    typeInput.value = type;
                }
                filterForm.submit();
            });
        });
    }
});
</script>
@endpush
@endsection
