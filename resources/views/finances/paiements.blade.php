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
    .finance-tabs .nav-link {
        color: var(--theme-accent);
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
    <div class="breadcrumb-title pe-3">{{ __('finances.add_breadcrumb') }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('finances.index') }}"><i class="bi bi-house"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ __('finances.breadcrumb_list_paiements') }}</li>
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
                        {{ __('finances.receipt_hash', ['id' => $paymentId]) }}
                    </a>
                    <a href="{{ route('finances.paiements.thermique', $paymentId) }}" class="btn btn-sm btn-outline-success">
                        {{ __('finances.thermal_hash', ['id' => $paymentId]) }}
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
        <strong>{{ __('finances.ignored_lines') }}</strong>
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
        @include('finances.partials.menu', ['active' => 'paiements'])
    </div>

    <div class="col-12 col-md-9 pt-4 pt-md-0 p-md-3">
        <div class="card theme-card w-100">
            <div class="card-header theme-header">
                <i class="bi bi-cash-coin me-1"></i>
                {{ $isPublicSchool ? __('finances.title_public') : __('finances.title_private') }}
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('finances.paiements.filter') }}" class="row g-3" id="paymentFilterForm" data-auto-filter="true">
                    @csrf
                    <div class="col-12">
                        <p class="mb-1">{{ __('finances.filtered_by') }}</p>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_classe">{{ __('finances.label_classe') }}<span class="text-danger fs-6">*</span></label>
                        <select class="single-select form-select auto-submit-payment" id="id_classe" name="id_classe" required>
                            <option value="">{{ __('finances.select_classe') }}</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" @selected(($filters['id_classe'] ?? '') == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_annee">{{ __('finances.label_annee') }} <span class="text-danger fs-6">*</span></label>
                        <select class="single-select form-select auto-submit-payment" id="id_annee" name="id_annee" required>
                            <option value="">{{ __('finances.select_annee') }}</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected(($filters['id_annee'] ?? '') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="id_trimestre">{{ __('finances.label_periode') }}<span class="text-danger fs-6">*</span></label>
                        <select class="single-select form-select auto-submit-payment" id="id_trimestre" name="id_trimestre" required>
                            <option value="">{{ __('finances.select_periode') }}</option>
                            @foreach($trimestres as $trimestre)
                                <option value="{{ $trimestre->id_trimestre }}" @selected($selectedTrimestre == $trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="date_paiement">{{ __('finances.label_date') }}<span class="text-danger fs-6">*</span></label>
                        <input class="form-control" type="date" required name="date_paiement" id="date_paiement" value="{{ $paymentDate }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="reference">{{ __('finances.label_reference') }} <span class="text-danger fs-6">*</span></label>
                        <input type="text" class="form-control" id="reference" value="{{ $newRef }}" readonly>
                    </div>
                    <div class="col-md-8 d-flex align-items-end justify-content-end">
                        <input type="hidden" name="type_planification" value="{{ $selectedType }}">
                        <button type="submit" class="btn btn-primary d-none">
                            <i class="bi bi-search me-1"></i>{{ __('finances.show_button') }}
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
                        <a class="nav-link @if($selectedType === '') active @endif" href="#" data-filter-type="">{{ __('finances.tab_tous') }}</a>
                    </li>
                    @if($isPublicSchool)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'cooperative') active @endif" href="#" data-filter-type="cooperative">{{ __('finances.tab_cooperative') }}</a>
                    </li>
                    @else
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'trimestriel') active @endif" href="#" data-filter-type="trimestriel">{{ __('finances.tab_trimestriel') }}</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'mensuel') active @endif" href="#" data-filter-type="mensuel">{{ __('finances.tab_mensuel') }}</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'annuel') active @endif" href="#" data-filter-type="annuel">{{ __('finances.tab_annuel') }}</a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link @if($selectedType === 'tranche') active @endif" href="#" data-filter-type="tranche">{{ __('finances.tab_tranche') }}</a>
                    </li>
                    @endif
            </ul>

            @if(!$caisse)
                <div class="alert alert-danger">{{ __('finances.need_active_caisse') }}</div>
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
                            <th>{{ __('finances.th_num') }}</th>
                            <th>{{ __('finances.th_eleve') }}</th>
                            <th>{{ $isPublicSchool ? __('finances.th_cooperative') : __('finances.th_motif') }}</th>
                            <th>{{ __('finances.th_parent') }}</th>
                            <th>{{ __('finances.th_telephone') }}</th>
                            <th>{{ __('finances.th_montant_total') }}</th>
                            <th>{{ __('finances.th_montant_reste') }}</th>
                            <th>{{ __('finances.th_montant_a_payer') }}</th>
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
                                    <input type="text" name="motif[]" value="{{ $isPublicSchool ? __('finances.th_cooperative') : $row->planification->motif }}" class="form-control form-control-sm">
                                    @if($row->tranche)
                                        @php
                                            $courante = $row->tranche['courante'];
                                            $tranchesSoldeesText = __('finances.tranches_soldees', ['soldees' => $row->tranche['soldees'], 'total' => $row->tranche['total']]);
                                            if ($courante) {
                                                $trancheMontant = number_format($courante['reste'], 0, ',', ' ');
                                                $trancheDate = \Illuminate\Support\Carbon::parse($courante['date_limite'])->format('d/m/Y');
                                                $trancheAvantLeText = __('finances.tranche_avant_le', ['libelle' => $courante['libelle'], 'montant' => $trancheMontant, 'date' => $trancheDate]);
                                            }
                                        @endphp
                                        <div class="small mt-1 {{ $row->tranche['en_retard'] ? 'text-danger fw-semibold' : 'text-muted' }}">
                                            {{ $tranchesSoldeesText }}
                                            @if($courante)
                                                <br>{{ $trancheAvantLeText }}@if($courante['en_retard']) {{ __('finances.tranche_en_retard') }}@endif
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td style="min-width: 220px;">
                                    <select name="parent_id[]" class="form-select form-select-sm payer-select">
                                        <option value="">{{ __('finances.choose_generic') }}</option>
                                        @foreach($row->parents as $parent)
                                            <option value="{{ $parent->id_parent }}" data-phone="{{ $parent->telephone_parent }}">
                                                {{ $parent->nom_prenom_parent }}
                                            </option>
                                        @endforeach
                                        <option value="autre">{{ __('finances.autre_personne') }}</option>
                                    </select>
                                    <input type="text" name="autre_personne_nom[]" class="form-control form-control-sm mt-2 other-name d-none" placeholder="{{ __('finances.payer_name_placeholder') }}">
                                </td>
                                <td style="min-width: 170px;">
                                    <input type="text" class="form-control form-control-sm payer-phone" readonly>
                                    <input type="text" name="autre_personne_telephone[]" class="form-control form-control-sm mt-2 other-phone d-none autre-tel" placeholder="{{ __('finances.payer_phone_placeholder') }}">
                                </td>
                                <td>@devise($row->montant_total)</td>
                                <td class="reste-cell" data-original-reste="{{ $row->reste_a_payer }}">
                                    <span class="current-reste">@devise($row->reste_a_payer)</span>
                                </td>
                                <td>
                                    <input type="number" name="montant_recu[]" class="form-control form-control-sm montant_recu" min="1" max="{{ $row->reste_a_payer }}" value="{{ $row->a_payer_maintenant }}">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">{{ $isPublicSchool ? __('finances.empty_coop_students') : __('finances.empty_type_students') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2 d-flex justify-content-between flex-wrap gap-2">
                <button type="button" id="btnPrintPaiements" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i>{{ __('finances.print_payment_list') }}
                </button>
                <button type="submit" id="btn-valider-paiement" name="envoie_re" class="btn btn-primary" @disabled(!$canPay || !$caisse || $paymentRows->isEmpty())>
                    {{ __('finances.validate_payments') }}
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script src="{{ asset('assets/mon_js/html2pdf.bundle.min.js') }}"></script>
@php
    $paiementsI18n = [
        'choose' => __('finances.choose_generic'),
        'printSheetTitle' => __('finances.print_sheet_title'),
        'printSheetMeta' => __('finances.print_sheet_meta'),
        'deviseSymbole' => \App\Support\Devise::symbole(),
        'deviseDecimales' => \App\Support\Devise::decimales(),
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    const i18n = @json($paiementsI18n);
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
                span.innerText = text === i18n.choose ? '' : text;
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

            const metaLine = i18n.printSheetMeta
                .replace(':classe', className)
                .replace(':annee', anneeName)
                .replace(':trimestre', trimName);
            container.innerHTML = `
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-uppercase mb-1" style="color: #333;">${i18n.printSheetTitle}</h3>
                    <div class="fs-6 text-muted mb-2">${metaLine}</div>
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
            
            const formatted = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: i18n.deviseDecimales, maximumFractionDigits: i18n.deviseDecimales }).format(newReste) + ' ' + i18n.deviseSymbole;
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
