@extends('layouts.app')

@section('content')
    @php
        $user = Auth::user();
        $canEditEleve = $user->droit === 'SupAdmin' || $user->userHasPermission('eleves_modification');
        $canDeleteEleve = $user->droit === 'SupAdmin' || $user->userHasAnyPermission(['eleves_supprimer', 'eleves_suppression']);
        $canTransferEleve = $user->droit === 'SupAdmin' || $user->userHasPermission('eleves_modification');
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Élèves</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste des élèves inscrits</li>
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

    <!-- Filters (Disposition Alliance-Team) -->
    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">Liste des élèves inscrits</h5>
            </div>
            <div class="card-body p-4">
                <p class="mb-2 fw-bold text-muted">Filtré par</p>
                <form action="{{ route('eleves.index') }}" method="POST" class="row g-3" data-auto-filter="true">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label" for="id_classe">Classe</label>
                        <select class="form-select" id="id_classe" name="id_classe">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" {{ request('id_classe') == $classe->id_classe ? 'selected' : '' }}>
                                    {{ $classe->nom_classe }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="id_annee">Année Scolaire</label>
                        <select class="form-select" id="id_annee" name="id_annee">
                            <option value="">Toutes les années</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" {{ request('id_annee') == $annee->id_anneeScolaire ? 'selected' : '' }}>
                                    {{ $annee->annee }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="search_eleve">Recherche</label>
                        <div class="input-group">
                            <input type="text" name="search" id="search_eleve" placeholder="Tapez nom, prénom ou matricule..." class="form-control" value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Actions (Disposition Alliance-Team) -->
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
                        <i class="bi bi-printer me-2"></i> Imprimer la liste filtrée
                    </button>
                </form>
                <div class="small text-muted mt-1" id="print-selection-help">
                    Cochez des élèves pour imprimer uniquement la sélection.
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
                        <i class="bi bi-file-earmark-excel me-2"></i> Exporter la liste filtrée
                    </button>
                </form>
                <div class="small text-muted mt-1" id="export-selection-help">
                    Cochez des élèves pour exporter uniquement la sélection.
                </div>
            @endif
        </div>
    </div>

    <!-- Main Card (Disposition Alliance-Team) -->
    <div class="card theme-card shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div class="ms-auto">
                    <a href="{{ route('inscriptions.create') }}" class="btn px-4 theme-pill-active">
                        <i class="bi bi-plus-lg me-2"></i>Ajouter
                    </a>
                    <a href="{{ route('inscriptions.group.create') }}" class="btn btn-primary px-4 ms-2">
                        <i class="bi bi-people me-2"></i>Groupe
                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-3">
                <!-- Nav pills -->
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-2 theme-pill-active">
                            <i class="bi bi-list-task me-2"></i>Liste Inscription
                        </button>
                    </li>
                </ul>

                <div class="text-muted small">
                    {{ number_format($eleves->count(), 0, ',', ' ') }} élève(s)
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
                                <th>N°</th>
                                <th>Prénom & Nom</th>
                                <th>Matricule</th>
                                <th>Classe</th>
                                <th>Année</th>
                                <th>Genre</th>
                                <th>Date naissance</th>
                                <th>Lieu naissance</th>
                                <th>Adresse</th>
                                <th>Cas social</th>
                                <th>Date inscription</th>
                                <th>Photo</th>
                                <th class="text-center">Action</th>
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
                                    <td>{{ $eleve->classe?->nom_classe ?? 'Non renseignée' }}</td>
                                    <td>{{ $annees->firstWhere('id_anneeScolaire', $eleve->id_annee)?->annee ?? 'Non renseignée' }}</td>
                                    <td>
                                        @if($eleve->genre_eleve == 'Masculin')
                                            <span class="text-primary"><i class="bi bi-gender-male me-1"></i> Masculin</span>
                                        @else
                                            <span class="text-pink"><i class="bi bi-gender-female me-1"></i> Féminin</span>
                                        @endif
                                    </td>
                                    <td>{{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : 'Non renseignée' }}</td>
                                    <td>{{ $eleve->lieu_naiss ?: 'Non renseigné' }}</td>
                                    <td>{{ $eleve->adresse_eleve ?: 'Non renseignée' }}</td>
                                    <td>{{ $eleve->cas_social ?: 'normal' }}</td>
                                    <td>{{ $eleve->date_inscription ? \Carbon\Carbon::parse($eleve->date_inscription)->format('d/m/Y') : 'Non renseignée' }}</td>
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
                                                            <i class="bi bi-pencil text-warning me-2"></i>Modifier
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($canTransferEleve)
                                                    <li>
                                                        <form action="{{ route('eleves.transfer', $eleve->id_eleve) }}" method="POST" data-transfer-form>
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-arrow-left-right text-success me-2"></i>Transférer
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                                @if($canDeleteEleve)
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('eleves.destroy', $eleve->id_eleve) }}" method="POST" data-confirm-delete data-confirm-title="Retirer cet élève ?" data-confirm-text="L’élève ne sera plus visible dans les listes actives, mais son historique restera conservé.">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-trash me-2"></i>Retirer de la liste active
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
                                    <td colspan="14" class="text-center py-4 text-muted">Aucun élève trouvé.</td>
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
                    <h5 class="fw-bold mb-2">Aucune liste affichée pour le moment</h5>
                    <p class="text-muted mb-0">
                        Sélectionnez une classe et une année scolaire dans les filtres, puis validez la recherche pour afficher les élèves inscrits.
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
            color: var(--theme-primary);
            background: var(--accent-light);
        }
    </style>

    <script>
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
                    ? `<i class="bi bi-printer me-2"></i> Imprimer ${ids.length} élève(s) coché(s)`
                    : `<i class="bi bi-printer me-2"></i> Imprimer la liste filtrée`;
            }
            if (exportButton) {
                exportButton.innerHTML = ids.length > 0
                    ? `<i class="bi bi-file-earmark-excel me-2"></i> Exporter ${ids.length} élève(s) coché(s)`
                    : `<i class="bi bi-file-earmark-excel me-2"></i> Exporter la liste filtrée`;
            }
            if (printHelp) {
                printHelp.textContent = ids.length > 0
                    ? 'Seuls les élèves cochés seront envoyés dans le PDF.'
                    : 'Cochez des élèves pour imprimer uniquement la sélection.';
            }
            if (exportHelp) {
                exportHelp.textContent = ids.length > 0
                    ? 'Seuls les élèves cochés seront envoyés dans le fichier Excel.'
                    : 'Cochez des élèves pour exporter uniquement la sélection.';
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
                    if (confirm(form.dataset.confirmText || 'Confirmer la suppression ?')) {
                        form.submit();
                    }
                    return;
                }
                Swal.fire({
                    title: form.dataset.confirmTitle || 'Confirmer la suppression ?',
                    text: form.dataset.confirmText || 'Cette action est définitive.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, continuer',
                    cancelButtonText: 'Annuler'
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
                    title: 'Transférer cet élève',
                    html: `
                        <input id="swal-destination" class="swal2-input" placeholder="Nouvelle école / destination">
                        <input id="swal-motif" class="swal2-input" placeholder="Motif du transfert">
                        <select id="swal-travail" class="swal2-input">
                            <option value="">Travail scolaire</option>
                            <option value="Très bon">Très bon</option>
                            <option value="Bon">Bon</option>
                            <option value="Moyen">Moyen</option>
                            <option value="Insuffisant">Insuffisant</option>
                        </select>
                        <select id="swal-conduite" class="swal2-input">
                            <option value="">Conduite</option>
                            <option value="Excellente">Excellente</option>
                            <option value="Bonne">Bonne</option>
                            <option value="Passable">Passable</option>
                            <option value="À surveiller">À surveiller</option>
                        </select>
                    `,
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Enregistrer le transfert',
                    cancelButtonText: 'Annuler',
                    preConfirm: () => {
                        const destination = document.getElementById('swal-destination').value.trim();
                        const motif = document.getElementById('swal-motif').value.trim();
                        const travail = document.getElementById('swal-travail').value;
                        const conduite = document.getElementById('swal-conduite').value;
                        if (!destination || !motif || !conduite) {
                            Swal.showValidationMessage('Destination, motif et conduite sont obligatoires.');
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
