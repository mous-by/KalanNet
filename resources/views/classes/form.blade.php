@extends('layouts.app')

@push('styles')
    <style>
        .matiere-check-card {
            cursor: pointer;
            user-select: none;
            background-color: #fff;
            transition: background-color .15s ease, border-color .15s ease;
        }
        .matiere-check-card:hover {
            background-color: #f8f9fa;
            border-color: var(--theme-accent) !important;
        }
        .matiere-check-card.is-checked {
            background-color: #eef6ff;
            border-color: var(--theme-accent) !important;
            border-width: 2px;
        }
        .matiere-checkbox-lg {
            width: 1.9em;
            height: 1.9em;
            cursor: pointer;
        }
    </style>
@endpush

@section('content')
    @php
        $isEdit = $mode === 'edit';
        $action = $isEdit ? route('classes.update', $classe->id_classe) : route('classes.store');
        $oldMatieres = old('id_matiere');
        $oldEnseignants = old('id_enseignants', []);
        $oldCoefficients = old('coefficient', []);
        $oldDomaines = old('id_domaine', []);
        $oldGroupes = old('groupe', []);
        $initialRows = collect();
        $domaineDefaut = fn ($matiere) => \App\Support\Domaines::defautPourMatiere($domaines, $matiere?->domaine_defaut);
        $afficherDomaines = $francoArabe && $domaines->isNotEmpty();

        if (is_array($oldMatieres)) {
            $initialRows = collect($oldMatieres)->map(function ($idMatiere, $index) use ($matieres, $oldEnseignants, $oldCoefficients, $oldDomaines, $oldGroupes) {
                return [
                    'id_matiere' => (int) $idMatiere,
                    'nom_matiere' => optional($matieres->firstWhere('id_matiere', (int) $idMatiere))->nom_matiere,
                    'id_enseignants' => $oldEnseignants[$index] ?? null,
                    'coefficient' => $oldCoefficients[$index] ?? 1,
                    'id_domaine' => $oldDomaines[$index] ?? null,
                    'groupe' => $oldGroupes[$index] ?? null,
                ];
            })->filter(fn ($row) => $row['nom_matiere']);
        } elseif (!$isEdit && !empty($lignesSuite)) {
            // Classe suivante : matières reprises de la classe précédente.
            $initialRows = $lignesSuite;
        } elseif ($isEdit) {
            $initialRows = $lignes->map(fn ($ligne) => [
                'id_matiere' => $ligne->id_matiere,
                'nom_matiere' => $ligne->matiere->nom_matiere ?? 'Matière supprimée',
                'id_enseignants' => $ligne->id_enseignants,
                'coefficient' => $ligne->coefficient ?? 1,
                'id_domaine' => $ligne->id_domaine ?: $domaineDefaut($ligne->matiere),
                'groupe' => $ligne->groupe,
            ]);
        }

        $matieresJson = $matieres->map(fn ($matiere) => [
            'id' => $matiere->id_matiere,
            'nom' => $matiere->nom_matiere,
            'ordres' => $matiere->ordres->pluck('ordre_enseignement')->values(),
            'domaine' => $domaineDefaut($matiere),
        ])->values();
        $domainesJson = $domaines->map(fn ($d) => ['id' => $d->id_domaine, 'nom' => $d->nom, 'partie' => $d->partie])->values();
        $enseignantsJson = $enseignants->map(fn ($enseignant) => [
            'id' => $enseignant->id_enseignant,
            'nom' => $enseignant->nom_prenom_enseignant,
        ])->values();
        $classeFormI18n = [
            'none' => __('classes.none'),
            'remove' => __('classes.remove'),
            'matieresShownFor' => __('classes.matieres_shown_for'),
            'chooseOrdreHelp' => __('classes.choose_ordre_help'),
            'sansDomaine' => __('classes.sans_domaine'),
            'groupeAuto' => __('classes.groupe_auto'),
            'groupePrincipale' => __('classes.groupe_principale'),
            'groupeSecondaire' => __('classes.groupe_secondaire'),
        ];
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('classes.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">{{ __('classes.breadcrumb_list') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $isEdit ? __('classes.modification') : __('classes.enregistrement') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">
            {{ $errors->first() }}
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    @if(!$isEdit && !empty($classeSource))
        <div class="alert alert-info border-0 border-start border-info border-4">
            <i class="bi bi-copy me-2"></i>{{ __('classes.suite_info', ['classe' => $classeSource->nom_classe]) }}
        </div>
    @endif

    <form method="POST" action="{{ $action }}" id="classe-form">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <a href="{{ route('classes.index') }}" class="btn btn-light border px-3"><i class="bi bi-arrow-left me-1"></i>{{ __('classes.breadcrumb_list') }}</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-circle me-1"></i>{{ $isEdit ? __('classes.action_edit') : __('classes.save') }}</button>
        </div>

        <div class="card border-top border-4 border-primary shadow-sm mb-4">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ $isEdit ? __('classes.edit_title') : __('classes.new_title') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if($estSante)
                        <div class="col-md-6">
                            <label class="form-label" for="id_filiere">{{ __('classes.filiere_label') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="id_filiere" name="id_filiere" required>
                                <option value="">{{ __('classes.choose') }}</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->id_filiere }}" @selected((string) old('id_filiere', $classe->id_filiere) === (string) $filiere->id_filiere)>{{ $filiere->nom_filiere }}</option>
                                @endforeach
                            </select>
                            @if($filieres->isEmpty())
                                <small class="text-danger d-block mt-1">{{ __('classes.no_filiere_created') }} <a href="{{ route('configuration.filieres') }}">{{ __('classes.create_one_link') }}</a>.</small>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="nom_classe">{{ __('classes.th_nom') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom_classe" name="nom_classe" value="{{ old('nom_classe', $classe->nom_classe) }}" placeholder="{{ __('classes.name_placeholder_sante') }}" required>
                            <small class="text-muted d-block mt-1">{{ __('classes.name_suggested_help') }}</small>
                        </div>
                    @else
                        <div class="col-md-6">
                            <label class="form-label" for="nom_classe">{{ __('classes.th_nom') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom_classe" name="nom_classe" value="{{ old('nom_classe', $classe->nom_classe) }}" placeholder="{{ __('classes.name_placeholder') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="ordre_enseignement">{{ __('classes.ordre_label') }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="ordre_enseignement" name="ordre_enseignement" required>
                                <option value="">{{ __('classes.choose') }}</option>
                                @foreach($ordres as $value => $label)
                                    <option value="{{ $value }}" @selected(old('ordre_enseignement', $classe->ordreEnseignement) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="serie-champ" style="display:none">
                            <label class="form-label" for="serie">{{ __('classes.serie_label') }}</label>
                            <select class="form-select" id="serie" name="serie">
                                <option value="">{{ __('classes.serie_aucune') }}</option>
                                @foreach(\App\Support\Series::pourNiveau(11) as $code => [$libelleSerie, $niveauSerie])
                                    <option value="{{ $code }}" data-niveau="{{ $niveauSerie }}" @selected(old('serie', $classe->serie) === $code)>{{ $libelleSerie }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">{{ __('classes.serie_aide') }} <a href="{{ route('pedagogie.series') }}" target="_blank">{{ __('classes.serie_lien') }}</a></small>
                        </div>
                        @if($francoArabe)
                            @php $deuxMaitres = (bool) old('deux_maitres', $classe->deux_maitres); @endphp
                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light-subtle">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="deux_maitres" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="deux_maitres" name="deux_maitres" value="1" @checked($deuxMaitres)>
                                        <label class="form-check-label fw-semibold" for="deux_maitres">{{ __('classes.deux_maitres_label') }}</label>
                                    </div>
                                    <small class="text-muted d-block mt-1">{{ __('classes.deux_maitres_help') }}</small>
                                    <div class="row g-3 mt-1" id="deux-maitres-champs" @if(!$deuxMaitres) style="display:none" @endif>
                                        @foreach(['arabe' => 'id_maitre_arabe', 'francais' => 'id_maitre_francais'] as $partie => $champ)
                                            <div class="col-md-6">
                                                <label class="form-label" for="{{ $champ }}">{{ __('classes.maitre_'.$partie) }} <span class="text-danger">*</span></label>
                                                <select class="form-select maitre-partie" id="{{ $champ }}" name="{{ $champ }}" data-partie="{{ $partie }}">
                                                    <option value="">{{ __('classes.choose') }}</option>
                                                    @foreach($enseignants as $enseignant)
                                                        <option value="{{ $enseignant->id_enseignant }}" @selected((string) old($champ, $classe->$champ) === (string) $enseignant->id_enseignant)>{{ $enseignant->nom_prenom_enseignant }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        @php
            $maitresChoisis = collect(old('id_maitres', $classe->exists ? $classe->maitres->pluck('id_enseignant')->all() : []))->map(fn ($id) => (int) $id)->all();
        @endphp
        <div class="card border-top border-4 border-primary shadow-sm" id="jardin-sans-matieres" style="display:none;">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ __('classes.jardin_maitres') }}</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0 border-start border-info border-4 py-2">
                    <i class="bi bi-info-circle me-1"></i>{{ __('classes.jardin_sans_matieres') }}
                </div>
                <input type="hidden" name="id_maitres[]" value="">
                <div class="row row-cols-1 row-cols-md-3 g-2">
                    @forelse($enseignants as $enseignant)
                        <div class="col">
                            <label class="matiere-check-card d-flex align-items-center gap-2 border rounded-3 p-2 @if(in_array((int) $enseignant->id_enseignant, $maitresChoisis, true)) is-checked @endif" for="maitre_{{ $enseignant->id_enseignant }}">
                                <input type="checkbox" class="form-check-input maitre-jardin flex-shrink-0" id="maitre_{{ $enseignant->id_enseignant }}" name="id_maitres[]" value="{{ $enseignant->id_enseignant }}" @checked(in_array((int) $enseignant->id_enseignant, $maitresChoisis, true))>
                                <span>{{ $enseignant->nom_prenom_enseignant }}</span>
                            </label>
                        </div>
                    @empty
                        <div class="col text-muted">{{ __('classes.jardin_aucun_enseignant') }}</div>
                    @endforelse
                </div>
                @error('id_maitres.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card border-top border-4 border-primary shadow-sm" id="carte-matieres">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">{{ __('classes.matieres_associees') }}</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0 border-start border-info border-4 py-2" id="matiere-order-help">
                    {{ $estSante ? __('classes.matieres_help_sante') : __('classes.choose_ordre_help') }}
                </div>
                <div class="d-flex justify-content-center mb-3">
                    <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#matieresModal">
                        <i class="bi bi-plus-circle me-2"></i>{{ __('classes.add_matiere') }}
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('classes.th_matiere') }}</th>
                                @if($afficherDomaines)<th>{{ __('classes.th_domaine') }} <a href="{{ route('pedagogie.domaines') }}" class="small fw-normal ms-1" target="_blank">{{ __('classes.domaines_lien') }}</a></th>@endif
                                <th>{{ __('classes.th_enseignant') }}</th>
                                <th style="width: 140px;">{{ __('classes.th_coefficient') }}</th>
                                <th class="col-groupe" style="width: 190px;" title="{{ __('classes.groupe_aide') }}">{{ __('classes.th_groupe') }}</th>
                                <th class="text-center" style="width: 90px;">{{ __('classes.th_action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="table-matieres">
                            @foreach($initialRows as $row)
                                <tr data-matiere-id="{{ $row['id_matiere'] }}">
                                    <td>
                                        <span class="fw-bold">{{ $row['nom_matiere'] }}</span>
                                        <input type="hidden" name="id_matiere[]" value="{{ $row['id_matiere'] }}">
                                    </td>
                                    @if($afficherDomaines)
                                        <td>
                                            <select class="form-select domaine-select" name="id_domaine[]">
                                                <option value="">{{ __('classes.sans_domaine') }}</option>
                                                @foreach($domaines as $domaine)
                                                    <option value="{{ $domaine->id_domaine }}" @selected((string) $row['id_domaine'] === (string) $domaine->id_domaine)>{{ $domaine->nom }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    @endif
                                    <td>
                                        <select class="form-select enseignant-select" name="id_enseignants[]">
                                            <option value="">{{ __('classes.none') }}</option>
                                            @foreach($enseignants as $enseignant)
                                                <option value="{{ $enseignant->id_enseignant }}" @selected((string) $row['id_enseignants'] === (string) $enseignant->id_enseignant)>
                                                    {{ $enseignant->nom_prenom_enseignant }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="coefficient[]" class="form-control" min="0" max="5" step="0.01" value="{{ $row['coefficient'] }}" required>
                                    </td>
                                    <td class="col-groupe">
                                        <select class="form-select" name="groupe[]">
                                            <option value="">{{ __('classes.groupe_auto') }}</option>
                                            <option value="principale" @selected($row['groupe'] === 'principale')>{{ __('classes.groupe_principale') }}</option>
                                            <option value="secondaire" @selected($row['groupe'] === 'secondaire')>{{ __('classes.groupe_secondaire') }}</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-light btn-sm remove-row" title="{{ __('classes.remove') }}">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Hors de la carte des matières : reste visible au Jardin, qui n'a pas de matières. --}}
        <div class="d-flex justify-content-between align-items-center mt-4 form-actions-sticky">
            <a href="{{ route('classes.index') }}" class="btn btn-light border px-4"><i class="bi bi-arrow-left me-1"></i>{{ __('classes.back') }}</a>
            <button type="submit" class="btn btn-primary btn-lg px-5 fw-semibold"><i class="bi bi-check2-circle me-1"></i>{{ $isEdit ? __('classes.action_edit') : __('classes.save') }}</button>
        </div>
    </form>

    <!-- Modal Ajouter des matières -->
    <div class="modal fade" id="matieresModal" tabindex="-1" aria-labelledby="matieresModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--theme-accent) !important;">
                    <h5 class="modal-title fw-bold" id="matieresModalLabel">
                        <i class="bi bi-journal-plus me-2"></i>{{ __('classes.choose_matieres_title') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <input type="text" class="form-control form-control-lg" id="matiere_search" placeholder="{{ __('classes.search_matiere') }}">
                    </div>
                    <label class="matiere-check-card d-flex align-items-center gap-3 border rounded-3 p-3 mb-3" for="matiere_select_all">
                        <input type="checkbox" class="form-check-input matiere-checkbox-lg flex-shrink-0" id="matiere_select_all">
                        <span class="fw-bold">{{ __('classes.select_all') }}</span>
                    </label>
                    <div id="matiere_checkbox_list" class="row row-cols-1 row-cols-md-2 g-3" style="max-height: 420px; overflow-y: auto;">
                        @foreach($matieres as $matiere)
                            <div class="col matiere-option" data-id="{{ $matiere->id_matiere }}" data-nom="{{ strtolower($matiere->nom_matiere) }}" data-ordres="{{ $matiere->ordres->pluck('ordre_enseignement')->implode('|') }}">
                                <label class="matiere-check-card d-flex align-items-center gap-3 border rounded-3 p-3" for="matiere_chk_{{ $matiere->id_matiere }}">
                                    <input type="checkbox" class="form-check-input matiere-checkbox-lg matiere-checkbox flex-shrink-0" id="matiere_chk_{{ $matiere->id_matiere }}" value="{{ $matiere->id_matiere }}">
                                    <span class="fw-semibold fs-6">{{ $matiere->nom_matiere }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-muted small mb-0 mt-3" id="matiere_empty_msg" style="display:none;">{{ __('classes.no_matiere_available') }}</p>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">{{ __('classes.cancel') }}</button>
                    <button type="button" class="btn text-white px-4 fw-semibold" id="matiere_add_selection" style="background-color: var(--theme-accent) !important;">
                        <i class="bi bi-check2-circle me-1"></i>{{ __('classes.add_selection') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ordreMatiereMap = @json($ordreMatiereMap);
            const matieres = @json($matieresJson);
            const enseignants = @json($enseignantsJson);
            const domaines = @json($domainesJson);
            const afficherDomaines = @json($afficherDomaines);
            const deuxMaitres = document.getElementById('deux_maitres');
            const i18n = @json($classeFormI18n);
            const tbody = document.getElementById('table-matieres');
            const nomClasse = document.getElementById('nom_classe');
            const ordreSelect = document.getElementById('ordre_enseignement');
            const filiereSelect = document.getElementById('id_filiere');
            const filiereNoms = @json($filieres->pluck('nom_filiere', 'id_filiere'));
            const help = document.getElementById('matiere-order-help');
            const matieresModalEl = document.getElementById('matieresModal');
            const matiereList = document.getElementById('matiere_checkbox_list');
            const matiereSearch = document.getElementById('matiere_search');
            const matiereSelectAll = document.getElementById('matiere_select_all');
            const matiereAddSelection = document.getElementById('matiere_add_selection');
            const matiereEmptyMsg = document.getElementById('matiere_empty_msg');

            function enseignantOptions() {
                return '<option value="">' + i18n.none + '</option>' + enseignants.map(function (enseignant) {
                    return '<option value="' + enseignant.id + '">' + escapeHtml(enseignant.nom) + '</option>';
                }).join('');
            }

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, function (char) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
                });
            }

            // Colonne « Groupe » (matière principale / secondaire) : classes du secondaire seulement.
            function afficherGroupes() {
                const secondaire = !!ordreSelect && (ordreSelect.value || '').startsWith('secondaire');
                document.querySelectorAll('.col-groupe').forEach(cell => cell.style.display = secondaire ? '' : 'none');
            }
            if (ordreSelect) ordreSelect.addEventListener('change', afficherGroupes);
            afficherGroupes();

            // Série : 11ème et 12ème du secondaire général ; seules les séries du niveau sont proposées.
            const serieChamp = document.getElementById('serie-champ');
            const serieSelect = document.getElementById('serie');
            function afficherSerie() {
                if (!serieChamp || !ordreSelect) return;
                const niveau = parseInt((nomClasse.value.match(/^\s*(\d{1,2})/) || [])[1] || '0', 10);
                // 11ème seulement : la 10ème est orientée automatiquement, la 12ème est la dernière année.
                const visible = ordreSelect.value === 'secondairegenerale' && niveau === 11;
                serieChamp.style.display = visible ? '' : 'none';
                serieSelect.querySelectorAll('option[data-niveau]').forEach(option => {
                    option.hidden = Number(option.dataset.niveau) !== niveau;
                    if (option.hidden && option.selected) serieSelect.value = '';
                });
            }
            if (ordreSelect) ordreSelect.addEventListener('change', afficherSerie);

            // Jardin : ni matières ni notes, la carte des matières est masquée.
            function afficherMatieres() {
                const jardin = !!ordreSelect && ordreSelect.value === 'jardin';
                document.getElementById('carte-matieres').style.display = jardin ? 'none' : '';
                document.getElementById('jardin-sans-matieres').style.display = jardin ? '' : 'none';
                document.querySelectorAll('#jardin-sans-matieres input').forEach(champ => champ.disabled = !jardin);
                document.querySelectorAll('#table-matieres input, #table-matieres select').forEach(champ => champ.disabled = jardin);
            }
            document.querySelectorAll('.maitre-jardin').forEach(caseMaitre => caseMaitre.addEventListener('change', () => caseMaitre.closest('.matiere-check-card').classList.toggle('is-checked', caseMaitre.checked)));
            if (ordreSelect) ordreSelect.addEventListener('change', afficherMatieres);

            // Jardin : le nom se remplit avec la section qui manque à l'école (modifiable).
            const champNom = document.getElementById('nom_classe');
            const nomJardin = @json($nomJardinPropose ?? 'Petite section');
            let nomAuto = '';
            if (ordreSelect && champNom) {
                ordreSelect.addEventListener('change', function () {
                    const libre = champNom.value.trim() === '' || champNom.value === nomAuto;
                    if (ordreSelect.value === 'jardin' && libre) {
                        champNom.value = nomAuto = nomJardin;
                    } else if (ordreSelect.value !== 'jardin' && nomAuto !== '' && champNom.value === nomAuto) {
                        champNom.value = nomAuto = '';
                    }
                });
            }
            afficherMatieres();
            nomClasse.addEventListener('input', afficherSerie);
            afficherSerie();

            function domaineOptions(selectionne) {
                return '<option value="">' + escapeHtml(i18n.sansDomaine) + '</option>' + domaines.map(function (domaine) {
                    return '<option value="' + domaine.id + '"' + (Number(selectionne) === Number(domaine.id) ? ' selected' : '') + '>' + escapeHtml(domaine.nom) + '</option>';
                }).join('');
            }

            // Classe à deux maîtres : chaque matière revient au maître de la
            // partie (arabe / française) de son domaine.
            function maitreDeLaPartie(partie) {
                const select = document.querySelector('.maitre-partie[data-partie="' + partie + '"]');
                return select ? select.value : '';
            }

            function attribuerMaitre(row) {
                if (!deuxMaitres || !deuxMaitres.checked) return;
                const domaineSelect = row.querySelector('.domaine-select');
                const domaine = domaineSelect ? domaines.find(d => Number(d.id) === Number(domaineSelect.value)) : null;
                const maitre = domaine && domaine.partie ? maitreDeLaPartie(domaine.partie) : '';
                if (maitre) row.querySelector('.enseignant-select').value = maitre;
            }

            function attribuerTous() {
                tbody.querySelectorAll('tr[data-matiere-id]').forEach(attribuerMaitre);
            }

            if (deuxMaitres) {
                deuxMaitres.addEventListener('change', function () {
                    document.getElementById('deux-maitres-champs').style.display = deuxMaitres.checked ? '' : 'none';
                    attribuerTous();
                });
                document.querySelectorAll('.maitre-partie').forEach(select => select.addEventListener('change', attribuerTous));
            }
            tbody.addEventListener('change', function (event) {
                if (event.target.classList.contains('domaine-select')) {
                    attribuerMaitre(event.target.closest('tr'));
                }
                // Fondamental I (un seul maître) : l'enseignant choisi est repris
                // pour toutes les matières qui n'en ont pas encore ; on peut
                // ensuite le changer matière par matière.
                if (event.target.classList.contains('enseignant-select') && event.target.value && estFondamental1SansDeuxMaitres()) {
                    tbody.querySelectorAll('.enseignant-select').forEach(function (select) {
                        if (!select.value) select.value = event.target.value;
                    });
                }
            });

            function estFondamental1SansDeuxMaitres() {
                return !!ordreSelect && ordreSelect.value === 'fondamentale1' && !(deuxMaitres && deuxMaitres.checked);
            }

            function enseignantDeLaClasse() {
                const rempli = Array.from(tbody.querySelectorAll('.enseignant-select')).find(select => select.value);
                return rempli ? rempli.value : '';
            }

            function addMatiereRow(id) {
                if (!id || tbody.querySelector('[data-matiere-id="' + id + '"]')) return;

                const matiere = matieres.find(function (item) { return Number(item.id) === id; });
                if (!matiere) return;

                tbody.insertAdjacentHTML('beforeend',
                    '<tr data-matiere-id="' + matiere.id + '">' +
                    '<td><span class="fw-bold">' + escapeHtml(matiere.nom) + '</span><input type="hidden" name="id_matiere[]" value="' + matiere.id + '"></td>' +
                    (afficherDomaines ? '<td><select class="form-select domaine-select" name="id_domaine[]">' + domaineOptions(matiere.domaine) + '</select></td>' : '') +
                    '<td><select class="form-select enseignant-select" name="id_enseignants[]">' + enseignantOptions() + '</select></td>' +
                    '<td><input type="number" name="coefficient[]" class="form-control" min="0" max="5" step="0.01" value="1" required></td>' +
                    '<td class="col-groupe"><select class="form-select" name="groupe[]"><option value="">' + escapeHtml(i18n.groupeAuto) + '</option><option value="principale">' + escapeHtml(i18n.groupePrincipale) + '</option><option value="secondaire">' + escapeHtml(i18n.groupeSecondaire) + '</option></select></td>' +
                    '<td class="text-center"><button type="button" class="btn btn-light btn-sm remove-row" title="' + i18n.remove + '"><i class="bi bi-trash text-danger"></i></button></td>' +
                    '</tr>'
                );
                attribuerMaitre(tbody.lastElementChild);
                if (estFondamental1SansDeuxMaitres() && enseignantDeLaClasse()) {
                    tbody.lastElementChild.querySelector('.enseignant-select').value = enseignantDeLaClasse();
                }
                afficherGroupes();
            }

            function syncCardState(option) {
                const checkbox = option.querySelector('.matiere-checkbox');
                const card = option.querySelector('.matiere-check-card');
                card.classList.toggle('is-checked', checkbox.checked);
            }

            function filterMatiereCheckboxes() {
                const selectedOrder = ordreSelect ? ordreSelect.value : '';
                const expectedOrder = ordreSelect ? (ordreMatiereMap[selectedOrder] || '') : '';
                const search = matiereSearch.value.trim().toLowerCase();
                let visibleCount = 0;

                matiereList.querySelectorAll('.matiere-option').forEach(function (option) {
                    const id = Number(option.dataset.id);
                    const ordres = (option.dataset.ordres || '').split('|').filter(Boolean);
                    const alreadyAdded = !!tbody.querySelector('[data-matiere-id="' + id + '"]');
                    const matchesOrder = !expectedOrder || ordres.includes(expectedOrder);
                    const matchesSearch = !search || option.dataset.nom.includes(search);
                    const visible = matchesOrder && matchesSearch && !alreadyAdded;

                    option.style.display = visible ? '' : 'none';
                    if (!visible) {
                        option.querySelector('.matiere-checkbox').checked = false;
                    } else {
                        visibleCount++;
                    }
                    syncCardState(option);
                });

                matiereEmptyMsg.style.display = visibleCount === 0 ? '' : 'none';
                matiereSelectAll.checked = false;
                matiereSelectAll.closest('.matiere-check-card').classList.remove('is-checked');
            }

            function filterMatieresByOrder() {
                if (!ordreSelect) {
                    filterMatiereCheckboxes();
                    return;
                }

                const selectedOrder = ordreSelect.value;
                const expectedOrder = ordreMatiereMap[selectedOrder] || '';

                tbody.querySelectorAll('tr[data-matiere-id]').forEach(function (row) {
                    const matiere = matieres.find(item => Number(item.id) === Number(row.dataset.matiereId));
                    if (matiere && expectedOrder && !matiere.ordres.includes(expectedOrder)) {
                        row.remove();
                    }
                });

                filterMatiereCheckboxes();

                help.textContent = expectedOrder
                    ? i18n.matieresShownFor.replace(':ordre', expectedOrder)
                    : i18n.chooseOrdreHelp;
            }

            if (ordreSelect) {
                ordreSelect.addEventListener('change', filterMatieresByOrder);
                filterMatieresByOrder();
            } else if (help) {
                help.textContent = 'Toutes les matières disponibles sont proposées.';
                filterMatiereCheckboxes();
            }

            matiereSearch.addEventListener('input', filterMatiereCheckboxes);

            matiereList.addEventListener('change', function (event) {
                const checkbox = event.target.closest('.matiere-checkbox');
                if (!checkbox) return;
                syncCardState(checkbox.closest('.matiere-option'));
            });

            matiereSelectAll.addEventListener('change', function () {
                matiereSelectAll.closest('.matiere-check-card').classList.toggle('is-checked', matiereSelectAll.checked);
                matiereList.querySelectorAll('.matiere-option').forEach(function (option) {
                    if (option.style.display !== 'none') {
                        option.querySelector('.matiere-checkbox').checked = matiereSelectAll.checked;
                        syncCardState(option);
                    }
                });
            });

            matiereAddSelection.addEventListener('click', function () {
                matiereList.querySelectorAll('.matiere-checkbox:checked').forEach(function (checkbox) {
                    addMatiereRow(Number(checkbox.value));
                    checkbox.checked = false;
                });
                matiereSelectAll.checked = false;
                filterMatiereCheckboxes();
                bootstrap.Modal.getInstance(matieresModalEl)?.hide();
            });

            matieresModalEl.addEventListener('show.bs.modal', filterMatiereCheckboxes);
            matieresModalEl.addEventListener('hidden.bs.modal', function () {
                matiereSearch.value = '';
                matiereSelectAll.checked = false;
                filterMatiereCheckboxes();
            });

            tbody.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-row');
                if (button) {
                    button.closest('tr').remove();
                    filterMatiereCheckboxes();
                }
            });

            nomClasse.addEventListener('input', function () {
                const value = this.value.trim().toLowerCase();
                const niveaux = ['1er', '2eme', '3eme', '4eme', '5eme', '6eme', '7eme', '8eme', '9eme', '10eme', '11eme', '12eme'];
                if (niveaux.includes(value)) {
                    this.value = value + ' année';
                }
            });

            // École de Santé : propose le nom de la filière comme point de
            // départ du nom de classe, sans écraser une saisie manuelle.
            if (filiereSelect) {
                let nomClasseDirty = nomClasse.value.trim() !== '';

                function suggestNomClasse() {
                    if (nomClasseDirty) return;
                    const filiereNom = filiereNoms[filiereSelect.value];
                    if (!filiereNom) return;
                    nomClasse.value = filiereNom;
                }

                nomClasse.addEventListener('input', function () {
                    nomClasseDirty = this.value.trim() !== '';
                });

                filiereSelect.addEventListener('change', suggestNomClasse);
            }
        });
    </script>
@endpush
