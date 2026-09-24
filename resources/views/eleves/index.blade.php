@extends('layouts.app')

@section('content')
    @php
        $user = Auth::user();
        $canEditEleve = $user->droit === 'SupAdmin' || $user->userHasPermission('eleves_modification');
        $canDeleteEleve = $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['eleves_supprimer', 'eleves_suppression']);
        $canTransferEleve = $user->droit === 'SupAdmin' || $user->userHasPermission('eleves_modification');
        $elevesIndexI18n = [
            'printSelected' => __('eleves.print_selected'),
            'printFiltered' => __('eleves.print_filtered'),
            'exportSelected' => __('eleves.export_selected'),
            'exportFiltered' => __('eleves.export_filtered'),
            'printHelpDefault' => __('eleves.print_help_default'),
            'printHelpSelected' => __('eleves.print_help_selected'),
            'exportHelpDefault' => __('eleves.export_help_default'),
            'exportHelpSelected' => __('eleves.export_help_selected'),
            'swalConfirmDefault' => __('eleves.swal_confirm_default'),
            'swalConfirmDefinitive' => __('eleves.swal_confirm_definitive'),
            'swalYesContinue' => __('eleves.swal_yes_continue'),
            'cancel' => __('eleves.cancel'),
            'transferTitle' => __('eleves.transfer_title'),
            'transferDestinationPlaceholder' => __('eleves.transfer_destination_placeholder'),
            'transferMotifPlaceholder' => __('eleves.transfer_motif_placeholder'),
            'transferTravailLabel' => __('eleves.transfer_travail_label'),
            'transferTravailTresBon' => __('eleves.transfer_travail_tres_bon'),
            'transferTravailBon' => __('eleves.transfer_travail_bon'),
            'transferTravailMoyen' => __('eleves.transfer_travail_moyen'),
            'transferTravailInsuffisant' => __('eleves.transfer_travail_insuffisant'),
            'transferConduiteLabel' => __('eleves.transfer_conduite_label'),
            'transferConduiteExcellente' => __('eleves.transfer_conduite_excellente'),
            'transferConduiteBonne' => __('eleves.transfer_conduite_bonne'),
            'transferConduitePassable' => __('eleves.transfer_conduite_passable'),
            'transferConduiteSurveiller' => __('eleves.transfer_conduite_surveiller'),
            'transferSave' => __('eleves.transfer_save'),
            'transferValidation' => __('eleves.transfer_validation'),
        ];
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('eleves.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('eleves.list_title') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <!-- Filters -->
    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ __('eleves.list_title') }}</h5>
            </div>
            <div class="card-body p-4">
                <p class="mb-2 fw-bold text-muted">{{ __('eleves.filtered_by') }}</p>
                <form action="{{ route('eleves.index') }}" method="POST" class="row g-3" data-auto-filter="true">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label" for="id_classe">{{ __('eleves.th_classe') }}</label>
                        <select class="form-select" id="id_classe" name="id_classe">
                            <option value="">{{ __('eleves.all_classes') }}</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" {{ request('id_classe') == $classe->id_classe ? 'selected' : '' }}>
                                    {{ $classe->nom_classe }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="id_annee">{{ __('eleves.th_annee') }}</label>
                        <select class="form-select" id="id_annee" name="id_annee">
                            <option value="">{{ __('eleves.all_annees') }}</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" {{ request('id_annee') == $annee->id_anneeScolaire ? 'selected' : '' }}>
                                    {{ $annee->annee }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="search_eleve">{{ __('eleves.search') }}</label>
                        <div class="input-group">
                            <input type="text" name="search" id="search_eleve" placeholder="{{ __('eleves.search_placeholder') }}" class="form-control" value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">{{ __('eleves.search_button') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="row align-items-center mb-3">
        <div class="col-md-6 pt-2">
            @if($showList && $eleves->isNotEmpty())
                <form action="{{ route('eleves.list.pdf') }}" method="POST" target="_blank" id="print-eleves-form">
                    @csrf
                    <input type="hidden" name="id_classe" value="{{ request('id_classe') }}">
                    <input type="hidden" name="id_annee" value="{{ request('id_annee') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="selected_eleves" id="selected-eleves-input">
                    <button type="submit" class="btn btn-primary w-100" id="print-eleves-list">
                        <i class="bi bi-printer me-2"></i> {{ __('eleves.print_filtered') }}
                    </button>
                </form>
                <div class="small text-muted mt-1" id="print-selection-help">
                    {{ __('eleves.print_help_default') }}
                </div>
            @endif
        </div>
        <div class="col-md-6 pt-2">
            @if($showList && $eleves->isNotEmpty())
                <form action="{{ route('eleves.list.excel') }}" method="POST" id="export-eleves-form">
                    @csrf
                    <input type="hidden" name="id_classe" value="{{ request('id_classe') }}">
                    <input type="hidden" name="id_annee" value="{{ request('id_annee') }}">
                    <input type="hidden" name="search" value="{{ request('search') }}">
                    <input type="hidden" name="selected_eleves" id="selected-eleves-excel-input">
                    <button type="submit" class="btn btn-success w-100" id="export-eleves-list">
                        <i class="bi bi-file-earmark-excel me-2"></i> {{ __('eleves.export_filtered') }}
                    </button>
                </form>
                <div class="small text-muted mt-1" id="export-selection-help">
                    {{ __('eleves.export_help_default') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Main Card -->
    <div class="card theme-card shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div class="ms-auto">
                    <a href="{{ route('inscriptions.create') }}" class="btn px-4 theme-pill-active">
                        <i class="bi bi-plus-lg me-2"></i>{{ __('eleves.add') }}
                    </a>
                    <a href="{{ route('inscriptions.group.create') }}" class="btn btn-primary px-4 ms-2">
                        <i class="bi bi-people me-2"></i>{{ __('eleves.add_group') }}
                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-3">
                <!-- Nav pills -->
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-2 theme-pill-active">
                            <i class="bi bi-list-task me-2"></i>{{ __('eleves.nav_list') }}
                        </button>
                    </li>
                </ul>

                <div class="text-muted small">
                    {{ __('eleves.count_students', ['count' => number_format($eleves->count(), 0, ',', ' ')]) }}
                </div>
            </div>

            @if($showList)
                @php
                    $selectedClasse = $classes->firstWhere('id_classe', (int) request('id_classe'));
                    $selectedAnnee = $annees->firstWhere('id_anneeScolaire', (int) request('id_annee'));
                @endphp

                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-center"><input type="checkbox" id="check_all"></th>
                                <th>{{ __('eleves.th_num') }}</th>
                                <th>{{ __('eleves.th_name') }}</th>
                                <th>{{ __('eleves.th_matricule') }}</th>
                                <th>{{ __('eleves.th_classe') }}</th>
                                <th>{{ __('eleves.th_annee') }}</th>
                                <th>{{ __('eleves.th_genre') }}</th>
                                <th>{{ __('eleves.th_date_naissance') }}</th>
                                <th>{{ __('eleves.th_lieu_naissance') }}</th>
                                <th>{{ __('eleves.th_adresse') }}</th>
                                <th>{{ __('eleves.th_cas_social') }}</th>
                                <th>{{ __('eleves.th_date_inscription') }}</th>
                                <th>{{ __('eleves.th_photo') }}</th>
                                <th class="text-center">{{ __('eleves.th_action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($eleves as $index => $eleve)
                                <tr>
                                    <td class="text-center"><input type="checkbox" class="check_eleve" value="{{ $eleve->id_eleve }}"></td>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <h6 class="mb-0 fw-bold">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</h6>
                                    </td>
                                    <td><span class="badge bg-light text-dark font-monospace">{{ $eleve->matricule }}</span></td>
                                    <td>{{ $eleve->classe?->nom_classe ?? __('eleves.not_specified') }}</td>
                                    <td>{{ $annees->firstWhere('id_anneeScolaire', $eleve->id_annee)?->annee ?? __('eleves.not_specified') }}</td>
                                    <td>
                                        @if($eleve->genre_eleve == 'Masculin')
                                            <span class="text-primary"><i class="bi bi-gender-male me-1"></i> {{ __('eleves.genre_masculin') }}</span>
                                        @else
                                            <span class="text-pink"><i class="bi bi-gender-female me-1"></i> {{ __('eleves.genre_feminin') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : __('eleves.not_specified') }}</td>
                                    <td>{{ $eleve->lieu_naiss ?: __('eleves.not_specified_m') }}</td>
                                    <td>{{ $eleve->adresse_eleve ?: __('eleves.not_specified') }}</td>
                                    <td>{{ $eleve->cas_social ?: __('eleves.social_normal') }}</td>
                                    <td>{{ $eleve->date_inscription ? \Carbon\Carbon::parse($eleve->date_inscription)->format('d/m/Y') : __('eleves.not_specified') }}</td>
                                    <td class="text-center">
                                        <div class="avatar-sm bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            @if($eleve->image)
                                                <img src="{{ asset($eleve->image) }}" alt="{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}" class="rounded-circle w-100 h-100 object-fit-cover">
                                            @else
                                                <i class="bi bi-person text-muted"></i>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="action-dropdown-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="bx bx-dots-horizontal-rounded"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if($canEditEleve)
                                                    <li>
                                                        <a href="{{ route('eleves.edit', $eleve->id_eleve) }}" class="dropdown-item">
                                                            <i class="bi bi-pencil text-warning me-2"></i>{{ __('eleves.action_edit') }}
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($canTransferEleve)
                                                    <li>
                                                        <form action="{{ route('eleves.transfer', $eleve->id_eleve) }}" method="POST" data-transfer-form>
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-arrow-left-right text-success me-2"></i>{{ __('eleves.action_transfer') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                                @if($canDeleteEleve)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('eleves.destroy', $eleve->id_eleve) }}" method="POST" data-confirm-delete data-confirm-title="{{ __('eleves.confirm_remove_title') }}" data-confirm-text="{{ __('eleves.confirm_remove_text') }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-trash me-2"></i>{{ __('eleves.action_remove') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center py-4 text-muted">{{ __('eleves.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            @else
                <div class="empty-eleves-state text-center py-5 mt-3">
                    <div class="empty-eleves-icon mx-auto mb-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-funnel fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-2">{{ __('eleves.empty_state_title') }}</h5>
                    <p class="text-muted mb-0">
                        {{ __('eleves.empty_state_text') }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <style>
        .text-pink { color: #ec4899; }
        .empty-eleves-state {
            border: 1px dashed var(--bs-border-color);
            border-radius: 12px;
            background: var(--bs-light);
        }
        .empty-eleves-icon {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            color: var(--theme-accent);
            background: var(--accent-light);
        }
    </style>

    <script>
        const elevesI18n = @json($elevesIndexI18n);
        const checkAll = document.getElementById('check_all');
        const selectedInput = document.getElementById('selected-eleves-input');
        const selectedExcelInput = document.getElementById('selected-eleves-excel-input');
        const printButton = document.getElementById('print-eleves-list');
        const exportButton = document.getElementById('export-eleves-list');
        const printHelp = document.getElementById('print-selection-help');
        const exportHelp = document.getElementById('export-selection-help');

        function updatePrintSelection() {
            const checked = Array.from(document.querySelectorAll('.check_eleve:checked'));
            const ids = checked.map((checkbox) => checkbox.value);

            if (selectedInput) {
                selectedInput.value = ids.join(',');
            }
            if (selectedExcelInput) {
                selectedExcelInput.value = ids.join(',');
            }
            if (printButton) {
                printButton.innerHTML = ids.length > 0
                    ? `<i class="bi bi-printer me-2"></i> ${elevesI18n.printSelected.replace(':count', ids.length)}`
                    : `<i class="bi bi-printer me-2"></i> ${elevesI18n.printFiltered}`;
            }
            if (exportButton) {
                exportButton.innerHTML = ids.length > 0
                    ? `<i class="bi bi-file-earmark-excel me-2"></i> ${elevesI18n.exportSelected.replace(':count', ids.length)}`
                    : `<i class="bi bi-file-earmark-excel me-2"></i> ${elevesI18n.exportFiltered}`;
            }
            if (printHelp) {
                printHelp.textContent = ids.length > 0
                    ? elevesI18n.printHelpSelected
                    : elevesI18n.printHelpDefault;
            }
            if (exportHelp) {
                exportHelp.textContent = ids.length > 0
                    ? elevesI18n.exportHelpSelected
                    : elevesI18n.exportHelpDefault;
            }
            if (checkAll) {
                const boxes = document.querySelectorAll('.check_eleve');
                checkAll.checked = boxes.length > 0 && checked.length === boxes.length;
                checkAll.indeterminate = checked.length > 0 && checked.length < boxes.length;
            }
        }

        checkAll?.addEventListener('change', function () {
            document.querySelectorAll('.check_eleve').forEach((checkbox) => {
                checkbox.checked = checkAll.checked;
            });
            updatePrintSelection();
        });

        document.querySelectorAll('.check_eleve').forEach((checkbox) => {
            checkbox.addEventListener('change', updatePrintSelection);
        });

        updatePrintSelection();

        document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!window.Swal) {
                    if (confirm(form.dataset.confirmText || elevesI18n.swalConfirmDefault)) {
                        form.submit();
                    }
                    return;
                }
                Swal.fire({
                    title: form.dataset.confirmTitle || elevesI18n.swalConfirmDefault,
                    text: form.dataset.confirmText || elevesI18n.swalConfirmDefinitive,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: elevesI18n.swalYesContinue,
                    cancelButtonText: elevesI18n.cancel
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        document.querySelectorAll('[data-transfer-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                if (!window.Swal) {
                    form.submit();
                    return;
                }

                Swal.fire({
                    title: elevesI18n.transferTitle,
                    html: `
                        <input id="swal-destination" class="swal2-input" placeholder="${elevesI18n.transferDestinationPlaceholder}">
                        <input id="swal-motif" class="swal2-input" placeholder="${elevesI18n.transferMotifPlaceholder}">
                        <select id="swal-travail" class="swal2-input">
                            <option value="">${elevesI18n.transferTravailLabel}</option>
                            <option value="Très bon">${elevesI18n.transferTravailTresBon}</option>
                            <option value="Bon">${elevesI18n.transferTravailBon}</option>
                            <option value="Moyen">${elevesI18n.transferTravailMoyen}</option>
                            <option value="Insuffisant">${elevesI18n.transferTravailInsuffisant}</option>
                        </select>
                        <select id="swal-conduite" class="swal2-input">
                            <option value="">${elevesI18n.transferConduiteLabel}</option>
                            <option value="Excellente">${elevesI18n.transferConduiteExcellente}</option>
                            <option value="Bonne">${elevesI18n.transferConduiteBonne}</option>
                            <option value="Passable">${elevesI18n.transferConduitePassable}</option>
                            <option value="À surveiller">${elevesI18n.transferConduiteSurveiller}</option>
                        </select>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: elevesI18n.transferSave,
                    cancelButtonText: elevesI18n.cancel,
                    preConfirm: () => {
                        const destination = document.getElementById('swal-destination').value.trim();
                        const motif = document.getElementById('swal-motif').value.trim();
                        const travail = document.getElementById('swal-travail').value;
                        const conduite = document.getElementById('swal-conduite').value;
                        if (!destination || !motif || !conduite) {
                            Swal.showValidationMessage(elevesI18n.transferValidation);
                            return false;
                        }
                        return { destination, motif, travail, conduite };
                    }
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    ['destination', 'motif', 'travail', 'conduite'].forEach((name) => {
                        form.querySelector(`input[name="${name}"]`)?.remove();
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = result.value[name] || '';
                        form.appendChild(input);
                    });
                    form.submit();
                });
            });
        });
    </script>
@endsection
