@extends('layouts.app')

@section('content')
@php
    // Ne recalculer activeTab que si le controleur ne l'a pas deja fourni
    // (ex: previewReinscription() force 'reinscription' meme quand la
    // requete POST n'a pas de ?tab= dans l'URL — l'ecraser ici cachait la
    // liste preparee derriere l'onglet "Inscription individuelle").
    $activeTab = $activeTab ?? request('tab', 'individual');
    $planificationRequired = $planificationRequired ?? true;
    $planificationLabel = $planificationRequired ? __('inscriptions.formule_paiement_label') : __('inscriptions.cooperative_label');
@endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('eleves.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('eleves.index') }}">{{ __('eleves.title') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('inscriptions.title') }}</li>
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

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <ul class="nav nav-pills mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'individual' ? 'active theme-pill-active' : '' }}" href="{{ route('inscriptions.index', ['tab' => 'individual']) }}">{{ __('inscriptions.tab_individual') }}</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'group' ? 'active theme-pill-active' : '' }}" href="{{ route('inscriptions.index', ['tab' => 'group']) }}">{{ __('inscriptions.tab_group') }}</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'reinscription' ? 'active theme-pill-active' : '' }}" href="{{ route('inscriptions.index', ['tab' => 'reinscription']) }}">{{ __('inscriptions.tab_reinscription') }}</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'individual' ? 'show active' : '' }}" id="tab-individual" role="tabpanel">
                    <div class="card border-0">
                        <div class="card-header bg-white">
                            <h5 class="mb-0 fw-bold">{{ __('inscriptions.individual_title') }}</h5>
                        </div>
                        <div class="card-body p-4 p-lg-5">
                            <form action="{{ route('inscriptions.store') }}" method="POST" enctype="multipart/form-data" data-planification-form>
                                @csrf
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_prenom') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="prenom_eleve" class="form-control rounded-3" value="{{ old('prenom_eleve') }}" placeholder="{{ __('eleves.label_prenom') }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_nom') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="nom_eleve" class="form-control rounded-3" value="{{ old('nom_eleve') }}" placeholder="{{ __('eleves.label_nom') }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_date_naissance') }}</label>
                                        <input type="date" name="date_naissance" class="form-control rounded-3" value="{{ old('date_naissance') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_lieu_naissance') }}</label>
                                        <input type="text" name="lieu_naiss" class="form-control rounded-3" value="{{ old('lieu_naiss') }}" placeholder="{{ __('inscriptions.lieu_naissance_placeholder') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.adresse_label') }}</label>
                                        <input type="text" name="adresse_eleve" class="form-control rounded-3" value="{{ old('adresse_eleve') }}" placeholder="{{ __('inscriptions.adresse_placeholder') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_genre') }} <span class="text-danger">*</span></label>
                                        <select name="genre_eleve" class="form-select rounded-3" required>
                                            <option value="">{{ __('inscriptions.select_generic') }}</option>
                                            <option value="Masculin" @selected(old('genre_eleve') === 'Masculin')>{{ __('eleves.genre_masculin') }}</option>
                                            <option value="Féminin" @selected(old('genre_eleve') === 'Féminin')>{{ __('eleves.genre_feminin') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_matricule') }}</label>
                                        <input type="text" name="matricule" class="form-control rounded-3" value="{{ old('matricule') }}" placeholder="{{ __('inscriptions.matricule_placeholder') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_date_inscription') }}</label>
                                        <input type="date" name="date_inscription" class="form-control rounded-3" value="{{ old('date_inscription', now()->toDateString()) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_cas_social') }}</label>
                                        <select name="cas_social" class="form-select rounded-3">
                                            @php
                                                $casSocialMap = [
                                                    'normal' => __('eleves.cas_social_normal'),
                                                    'Dipenser' => __('eleves.cas_social_dispense'),
                                                    'Malade' => __('eleves.cas_social_malade'),
                                                ];
                                            @endphp
                                            @foreach($casSocialMap as $value => $label)
                                                <option value="{{ $value }}" @selected(old('cas_social', 'normal') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_classe') }} <span class="text-danger">*</span></label>
                                        <select name="id_classe" class="form-select rounded-3" required data-planification-classe>
                                            <option value="">{{ __('inscriptions.select_classe') }}</option>
                                            @foreach($classes as $classe)
                                                <option value="{{ $classe->id_classe }}" @selected(old('id_classe') == $classe->id_classe)>{{ $classe->nom_classe }} - {{ $classe->ordreEnseignement }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_mode_paiement') }}</label>
                                        <select name="mode_paiement" class="form-select rounded-3">
                                            @php
                                                $modePaiementMap = [
                                                    '' => __('eleves.mode_paiement_non_defini'),
                                                    'Mensuel' => __('eleves.mode_paiement_mensuel'),
                                                    'Trimestriel' => __('eleves.mode_paiement_trimestriel'),
                                                    'Annuel' => __('eleves.mode_paiement_annuel'),
                                                ];
                                            @endphp
                                            @foreach($modePaiementMap as $value => $label)
                                                <option value="{{ $value }}" @selected(old('mode_paiement') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.lv2_label') }}</label>
                                        <select name="id_matiere_lv2" class="form-select rounded-3">
                                            <option value="">{{ __('eleves.lv2_none') }}</option>
                                            @foreach($matieresLv2 as $matiereLv2)
                                                <option value="{{ $matiereLv2->id_matiere }}" @selected(old('id_matiere_lv2') == $matiereLv2->id_matiere)>
                                                    {{ $matiereLv2->nom_matiere }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">{{ __('eleves.lv2_help') }}</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_annee') }} <span class="text-danger">*</span></label>
                                        <select name="id_annee" class="form-select rounded-3" required data-planification-annee>
                                            <option value="">{{ __('eleves.choose_annee') }}</option>
                                            @foreach($annees as $annee)
                                                <option value="{{ $annee->id_anneeScolaire }}" @selected(old('id_annee') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">
                                            {{ $planificationLabel }}
                                            @if($planificationRequired)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <select name="id_planification" class="form-select rounded-3" @required($planificationRequired) data-planification-select>
                                            <option value="">{{ $planificationRequired ? __('inscriptions.select_planification') : __('inscriptions.no_cooperative_no_fee') }}</option>
                                            @foreach($planifications as $planification)
                                                <option value="{{ $planification->id_planification }}" data-classe="{{ $planification->id_classe }}" data-annee="{{ $planification->id_annee }}" @selected(old('id_planification') == $planification->id_planification)>{{ $planificationRequired ? $planification->motif : __('inscriptions.cooperative_label') }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.photo_label') }}</label>
                                        <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                                    </div>

                                    <div class="col-12">
                                        <div class="card theme-card shadow-sm">
                                            <div class="card-header">
                                                <h6 class="mb-0 fw-bold">{{ __('inscriptions.parent_already_title') }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">{{ __('inscriptions.parent_label') }}</label>
                                                        <select name="parent_id" class="form-select">
                                                            <option value="">{{ __('inscriptions.no_attach_now') }}</option>
                                                            @foreach($parents as $parent)
                                                                <option value="{{ $parent->id_parent }}" @selected(old('parent_id') == $parent->id_parent)>{{ $parent->nom_prenom_parent }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">{{ __('inscriptions.lien_parente_label') }}</label>
                                                        <select name="lien_parent" class="form-select">
                                                            @php
                                                                $lienParentMap = [
                                                                    'Parent' => __('inscriptions.lien_parent_generic'),
                                                                    'Père' => __('parents.lien_pere'),
                                                                    'Mère' => __('parents.lien_mere'),
                                                                    'Frère' => __('parents.lien_frere'),
                                                                    'Sœur' => __('parents.lien_soeur'),
                                                                    'Tuteur' => __('parents.lien_tuteur'),
                                                                    'Tutrice' => __('parents.lien_tutrice'),
                                                                    'Autre' => __('parents.lien_autre'),
                                                                ];
                                                            @endphp
                                                            @foreach($lienParentMap as $value => $label)
                                                                <option value="{{ $value }}" @selected(old('lien_parent', 'Parent') === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">{{ __('parents.th_informer') }}</label>
                                                        <select name="informer" class="form-select">
                                                            <option value="Oui" @selected(old('informer', 'Oui') === 'Oui')>{{ __('parents.informer_oui') }}</option>
                                                            <option value="Non" @selected(old('informer') === 'Non')>{{ __('parents.informer_non') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-5">
                                        <hr class="my-4">
                                        <div class="d-flex justify-content-between">
                                            <button type="reset" class="btn btn-light px-5">{{ __('inscriptions.reset') }}</button>
                                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">{{ __('inscriptions.validate_inscription') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'group' ? 'show active' : '' }}" id="tab-group" role="tabpanel">
                    <div class="card theme-card border-0 shadow-sm">
                        <div class="card-header theme-header border-0">
                            <h5 class="mb-0 fw-bold">{{ __('inscriptions.tab_group') }}</h5>
                        </div>
                        <div class="card-body p-4 p-lg-5">
                            <form action="{{ route('inscriptions.group.import') }}" method="POST" enctype="multipart/form-data" data-planification-form>
                                @csrf
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.excel_file_label') }} <span class="text-danger">*</span></label>
                                        <input type="file" name="fichier_excel" class="form-control rounded-3" accept=".xls,.xlsx" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_classe') }} <span class="text-danger">*</span></label>
                                        <select name="id_classe" class="form-select rounded-3" required data-planification-classe>
                                            <option value="">{{ __('inscriptions.select_classe') }}</option>
                                            @foreach($classes as $classe)
                                                <option value="{{ $classe->id_classe }}">{{ $classe->nom_classe }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_annee') }} <span class="text-danger">*</span></label>
                                        <select name="id_annee" class="form-select rounded-3" required data-planification-annee>
                                            <option value="">{{ __('eleves.choose_annee') }}</option>
                                            @foreach($annees as $annee)
                                                <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">
                                            {{ $planificationLabel }}
                                            @if($planificationRequired)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <select name="id_planification" class="form-select rounded-3" @required($planificationRequired) data-planification-select>
                                            <option value="">{{ $planificationRequired ? __('inscriptions.select_planification') : __('inscriptions.no_cooperative_no_fee') }}</option>
                                            @foreach($planifications as $planification)
                                                <option value="{{ $planification->id_planification }}" data-classe="{{ $planification->id_classe }}" data-annee="{{ $planification->id_annee }}">{{ $planificationRequired ? $planification->motif : __('inscriptions.cooperative_label') }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_date_inscription') }}</label>
                                        <input type="date" name="date_inscription" class="form-control rounded-3" value="{{ now()->toDateString() }}">
                                    </div>
                                    <div class="col-12 text-end mt-3">
                                        <button type="submit" class="btn theme-pill-active px-5 py-2 fw-bold">{{ __('inscriptions.import_and_register_button') }}</button>
                                    </div>
                                </div>
                            </form>

                            <div class="mt-5 card theme-card shadow-sm border-0">
                                <div class="card-body p-4">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                                        <div>
                                            <h6 class="mb-2">{{ __('inscriptions.template_title') }}</h6>
                                            <p class="mb-2">{{ __('inscriptions.template_desc') }}</p>
                                            <a href="{{ route('inscriptions.group.template') }}" class="btn btn-outline-primary">{{ __('inscriptions.template_download_button') }}</a>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-4">
                                        <table class="table table-bordered table-sm mb-0 border-primary">
                                            <thead class="table-light bg-light-primary">
                                            <tr>
                                                <th>prenom_eleve</th>
                                                <th>nom_eleve</th>
                                                <th>date_naissance</th>
                                                <th>lieu_naissance</th>
                                                <th>adresse_eleve</th>
                                                <th>genre_eleve</th>
                                                <th>cas_social</th>
                                                <th>matricule</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>Issa</td>
                                                <td>Diallo</td>
                                                <td>2009-04-22</td>
                                                <td>Ségou</td>
                                                <td>Banankabougou</td>
                                                <td>Masculin</td>
                                                <td>Normal</td>
                                                <td></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'reinscription' ? 'show active' : '' }}" id="tab-reinscription" role="tabpanel">
                    <div class="card theme-card border-0 shadow-sm">
                        <div class="card-header theme-header border-0">
                            <h5 class="mb-0 fw-bold">{{ __('inscriptions.reinscription_smart_title') }}</h5>
                        </div>
                        <div class="card-body p-4 p-lg-5">
                            @php
                                $reinscriptionPreview = $reinscriptionPreview ?? null;
                                $reinscriptionFilters = $reinscriptionFilters ?? [];
                                $decisionLabels = [
                                    'passant' => __('inscriptions.decision_passant'),
                                    'redoublant' => __('inscriptions.decision_redoublant'),
                                    'admis_sortant' => __('inscriptions.decision_admis_sortant'),
                                    'diplome_sortant' => __('inscriptions.decision_diplome_sortant'),
                                    'en_attente_resultat' => __('inscriptions.decision_en_attente_resultat'),
                                    'ajourne' => __('inscriptions.decision_ajourne'),
                                    'abandon' => __('inscriptions.decision_abandon'),
                                    'exclu' => __('inscriptions.decision_exclu'),
                                ];
                                $proposalLabels = [
                                    'passant' => __('inscriptions.decision_passant'),
                                    'redoublant' => __('inscriptions.decision_redoublant'),
                                    'non_defini' => __('inscriptions.proposal_non_defini'),
                                    'admis_sortant' => __('inscriptions.decision_admis_sortant'),
                                    'diplome_sortant' => __('inscriptions.decision_diplome_sortant'),
                                    'en_attente_resultat' => __('inscriptions.proposal_en_attente_resultat'),
                                ];
                            @endphp

                            <div class="alert alert-info border-0 border-start border-info border-4">
                                <h6 class="mb-2">{{ __('inscriptions.reinscription_assistant_title') }}</h6>
                                <p class="mb-0">{{ __('inscriptions.reinscription_assistant_desc') }}</p>
                            </div>

                            <form action="{{ route('inscriptions.reinscription.store') }}" method="POST" class="mb-4">
                                @csrf
                                <input type="hidden" name="preview_reinscription" value="1">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.label_classe_actuelle') }} <span class="text-danger">*</span></label>
                                        <select name="source_classe_id" class="form-select rounded-3" required data-reinscription-source-class>
                                            <option value="">{{ __('inscriptions.choose_classe_generic') }}</option>
                                            @foreach($classes as $classe)
                                                @php
                                                    preg_match('/\d+/', \Illuminate\Support\Str::ascii((string) $classe->nom_classe), $classeLevelMatch);
                                                    $classeLevel = $classeLevelMatch[0] ?? '';
                                                @endphp
                                                <option value="{{ $classe->id_classe }}" data-level="{{ $classeLevel }}" @selected(($reinscriptionFilters['source_classe_id'] ?? old('source_classe_id')) == $classe->id_classe)>{{ $classe->nom_classe }} - {{ $classe->ordreEnseignement }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.label_annee_actuelle') }} <span class="text-danger">*</span></label>
                                        <select name="source_annee_id" class="form-select rounded-3" required data-reinscription-source-year>
                                            <option value="">{{ __('inscriptions.choose_annee_actuelle') }}</option>
                                            @foreach($annees as $annee)
                                                @php
                                                    preg_match('/(20\d{2}|19\d{2})/', (string) $annee->annee, $yearMatch);
                                                    $anneeStartYear = $yearMatch[1] ?? substr((string) $annee->date_debut, 0, 4);
                                                @endphp
                                                <option value="{{ $annee->id_anneeScolaire }}" data-start-year="{{ $anneeStartYear }}" @selected(($reinscriptionFilters['source_annee_id'] ?? old('source_annee_id')) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.label_annee_cible') }} <span class="text-danger">*</span></label>
                                        <select name="target_annee_id" class="form-select rounded-3" required data-reinscription-target-year>
                                            <option value="">{{ __('inscriptions.choose_annee_cible') }}</option>
                                            @foreach($annees as $annee)
                                                @php
                                                    preg_match('/(20\d{2}|19\d{2})/', (string) $annee->annee, $targetYearMatch);
                                                    $targetStartYear = $targetYearMatch[1] ?? substr((string) $annee->date_debut, 0, 4);
                                                @endphp
                                                <option value="{{ $annee->id_anneeScolaire }}" data-start-year="{{ $targetStartYear }}" @selected(($reinscriptionFilters['target_annee_id'] ?? old('target_annee_id')) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                                            @endforeach
                                        </select>
                                        <div class="small text-muted mt-1" data-reinscription-target-help></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.label_classe_cible_passants') }}</label>
                                        <select name="target_classe_id" class="form-select rounded-3" data-reinscription-target-class>
                                            <option value="">{{ __('inscriptions.auto_if_possible') }}</option>
                                            @foreach($classes as $classe)
                                                @php
                                                    preg_match('/\d+/', \Illuminate\Support\Str::ascii((string) $classe->nom_classe), $targetClasseLevelMatch);
                                                    $targetClasseLevel = $targetClasseLevelMatch[0] ?? '';
                                                @endphp
                                                <option value="{{ $classe->id_classe }}" data-level="{{ $targetClasseLevel }}" @selected(($reinscriptionFilters['target_classe_id'] ?? old('target_classe_id')) == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                                            @endforeach
                                        </select>
                                        <div class="alert alert-warning py-2 px-3 mt-2 mb-0 d-none" data-reinscription-class-help></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.label_date_reinscription') }}</label>
                                        <input type="date" name="date_reinscription" class="form-control rounded-3" value="{{ $reinscriptionFilters['date_reinscription'] ?? old('date_reinscription', now()->toDateString()) }}">
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-secondary border-0 py-2 px-3 mb-0" data-reinscription-guidance>
                                            {{ __('inscriptions.guidance_default') }}
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn theme-pill-active w-100 py-2 fw-bold d-none" data-reinscription-prepare>{{ __('inscriptions.prepare_list_button') }}</button>
                                    </div>
                                </div>
                            </form>

                            @if($reinscriptionPreview)
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted">{{ __('inscriptions.preview_classe_source') }}</div>
                                            <div class="fw-bold">{{ $reinscriptionPreview['sourceClasse']->nom_classe }}</div>
                                            @if($reinscriptionPreview['niveauExamen'])
                                                <div class="small">{{ __('inscriptions.preview_examen', ['value' => $reinscriptionPreview['niveauExamen']]) }}</div>
                                            @else
                                                <div class="small">{{ __('inscriptions.preview_seuil', ['value' => number_format($reinscriptionPreview['seuil'], 2, ',', ' ')]) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted">{{ __('inscriptions.preview_classe_proposee') }}</div>
                                            <div class="fw-bold">{{ $reinscriptionPreview['targetClasse']?->nom_classe ?? __('inscriptions.preview_sortie_transfert') }}</div>
                                            <div class="small">{{ $reinscriptionPreview['targetAnnee']?->annee }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted">{{ __('inscriptions.preview_propositions') }}</div>
                                            <div class="fw-bold">{{ __('inscriptions.preview_passant_count', ['count' => $reinscriptionPreview['stats']['passants']]) }}</div>
                                            <div class="small">{{ __('inscriptions.preview_redoublant_count', ['count' => $reinscriptionPreview['stats']['redoublants']]) }}</div>
                                            @if($reinscriptionPreview['niveauExamen'])
                                                <div class="small">{{ __('inscriptions.preview_sortant_count', ['count' => $reinscriptionPreview['stats']['sortants']]) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted">{{ __('inscriptions.preview_a_surveiller') }}</div>
                                            <div class="fw-bold">
                                                {{ $reinscriptionPreview['niveauExamen'] ? __('inscriptions.preview_en_attente_resultat_count', ['count' => $reinscriptionPreview['stats']['en_attente_resultat']]) : __('inscriptions.preview_sans_moyenne_count', ['count' => $reinscriptionPreview['stats']['sans_moyenne']]) }}
                                            </div>
                                            <div class="small">{{ __('inscriptions.preview_deja_reinscrits_count', ['count' => $reinscriptionPreview['stats']['deja_reinscrits']]) }}</div>
                                        </div>
                                    </div>
                                </div>

                                @if($reinscriptionPreview['rows']->isEmpty())
                                    <div class="alert alert-warning border-0 border-start border-warning border-4">
                                        {{ __('inscriptions.empty_reinscription') }}
                                    </div>
                                @else
                                    <form action="{{ route('inscriptions.reinscription.store') }}" method="POST" data-reinscription-form>
                                        @csrf
                                        <input type="hidden" name="source_classe_id" value="{{ $reinscriptionPreview['sourceClasse']->id_classe }}">
                                        <input type="hidden" name="source_annee_id" value="{{ $reinscriptionPreview['sourceAnnee']?->id_anneeScolaire }}">
                                        <input type="hidden" name="target_annee_id" value="{{ $reinscriptionPreview['targetAnnee']?->id_anneeScolaire }}">
                                        <input type="hidden" name="date_reinscription" value="{{ $reinscriptionPreview['date'] }}">

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="mb-1 fw-bold">{{ __('inscriptions.decisions_a_valider_title') }}</h6>
                                                <div class="small text-muted">{{ __('inscriptions.decisions_a_valider_desc') }}</div>
                                            </div>
                                            <button type="submit" class="btn btn-primary px-4 fw-bold">{{ __('inscriptions.validate_checked_button') }}</button>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" style="width: 48px;"><input type="checkbox" class="form-check-input" data-reinscription-check-all checked></th>
                                                    <th>{{ __('parents.th_eleve') }}</th>
                                                    <th>{{ __('eleves.label_matricule') }}</th>
                                                    @if($reinscriptionPreview['niveauExamen'])
                                                        <th>{{ __('inscriptions.th_examen') }}</th>
                                                        <th>{{ __('inscriptions.th_resultat_national') }}</th>
                                                        <th>{{ __('inscriptions.th_moyenne_examen') }}</th>
                                                    @else
                                                        <th>{{ __('inscriptions.th_moyenne_annuelle') }}</th>
                                                    @endif
                                                    <th>{{ __('inscriptions.th_proposition') }}</th>
                                                    <th>{{ __('inscriptions.th_decision_finale') }}</th>
                                                    <th>{{ __('inscriptions.th_classe_finale') }}</th>
                                                    <th>{{ __('inscriptions.th_observation') }}</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($reinscriptionPreview['rows'] as $row)
                                                    @php
                                                        $eleve = $row['eleve'];
                                                        $key = $eleve->id_eleve;
                                                        $proposal = $row['decision_proposee'] === 'non_defini' ? 'redoublant' : $row['decision_proposee'];
                                                        $blockedDecision = $row['decision_proposee'] === 'en_attente_resultat';
                                                    @endphp
                                                    <tr class="{{ $row['deja_reinscrit'] ? 'table-warning' : '' }}">
                                                        <td class="text-center">
                                                            <input type="checkbox" class="form-check-input" name="eleves[{{ $key }}][selected]" value="1" data-reinscription-check @checked(!$row['deja_reinscrit'] && !$blockedDecision) @disabled($row['deja_reinscrit'] || $blockedDecision)>
                                                            <input type="hidden" name="eleves[{{ $key }}][id_eleve]" value="{{ $eleve->id_eleve }}">
                                                        </td>
                                                        <td>
                                                            <div class="fw-bold">{{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }}</div>
                                                            @if($row['deja_reinscrit'])
                                                                <span class="badge bg-warning text-dark">{{ __('inscriptions.badge_deja_reinscrit') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $eleve->matricule ?: __('inscriptions.not_provided') }}</td>
                                                        @if($reinscriptionPreview['niveauExamen'])
                                                            <td>{{ $row['niveau_examen'] }}</td>
                                                            <td>{{ $row['resultat_national'] ?: __('inscriptions.not_available') }}</td>
                                                            <td>
                                                                @if($row['moyenne_examen'] === null)
                                                                    <span class="text-muted">{{ __('inscriptions.not_available') }}</span>
                                                                @else
                                                                    <span class="fw-bold">{{ number_format($row['moyenne_examen'], 2, ',', ' ') }}</span>
                                                                @endif
                                                            </td>
                                                        @else
                                                            <td>
                                                                @if($row['moyenne'] === null)
                                                                    <span class="text-muted">{{ __('inscriptions.not_available') }}</span>
                                                                @else
                                                                    <span class="fw-bold">{{ number_format($row['moyenne'], 2, ',', ' ') }}</span>
                                                                @endif
                                                            </td>
                                                        @endif
                                                        <td>{{ $proposalLabels[$row['decision_proposee']] ?? $row['decision_proposee'] }}</td>
                                                        <td>
                                                            <select name="eleves[{{ $key }}][decision]" class="form-select form-select-sm" data-reinscription-decision data-pass-classe="{{ $row['classe_cible_id'] }}" data-source-classe="{{ $reinscriptionPreview['sourceClasse']->id_classe }}" @disabled($row['deja_reinscrit'] || $blockedDecision)>
                                                                @foreach($decisionLabels as $value => $label)
                                                                    <option value="{{ $value }}" @selected($proposal === $value)>{{ $label }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <select name="eleves[{{ $key }}][id_classe]" class="form-select form-select-sm" data-reinscription-classe @disabled($row['deja_reinscrit'] || $blockedDecision)>
                                                                <option value="" @selected($row['classe_cible_id'] === null)>{{ __('inscriptions.aucune_classe') }}</option>
                                                                @foreach($classes as $classe)
                                                                    <option value="{{ $classe->id_classe }}" @selected((int) $row['classe_cible_id'] === (int) $classe->id_classe)>{{ $classe->nom_classe }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="eleves[{{ $key }}][motif_decision]" class="form-control form-control-sm" data-reinscription-observation placeholder="{{ __('inscriptions.motif_placeholder') }}" @disabled($row['deja_reinscrit'] || $blockedDecision)>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @php
        $reinscriptionI18n = [
            'missingClasseActuelle' => __('inscriptions.js_missing_classe_actuelle'),
            'missingAnneeActuelle' => __('inscriptions.js_missing_annee_actuelle'),
            'missingAnneeCible' => __('inscriptions.js_missing_annee_cible'),
            'guidanceAllReady' => __('inscriptions.js_guidance_all_ready'),
            'guidanceMissingPrefix' => __('inscriptions.js_guidance_missing_prefix'),
            'chooseSourceClassHelp' => __('inscriptions.js_choose_source_class_help'),
            'classTargetAutoHelp' => __('inscriptions.js_class_target_auto_help'),
            'terminalClassHelp' => __('inscriptions.js_terminal_class_help'),
            'noNextClassHelp' => __('inscriptions.js_no_next_class_help'),
            'targetYearAutoHelp' => __('inscriptions.js_target_year_auto_help'),
            'nextYearMissingHelp' => __('inscriptions.js_next_year_missing_help'),
            'motifPlaceholder' => __('inscriptions.motif_placeholder'),
            'motifRequiredPlaceholder' => __('inscriptions.motif_required_placeholder'),
        ];
    @endphp
    <script>
        document.querySelectorAll('[data-planification-form]').forEach((form) => {
            const classeSelect = form.querySelector('[data-planification-classe]');
            const anneeSelect = form.querySelector('[data-planification-annee]');
            const planificationSelect = form.querySelector('[data-planification-select]');

            if (!classeSelect || !anneeSelect || !planificationSelect) {
                return;
            }

            const options = Array.from(planificationSelect.options);
            const filterPlanifications = () => {
                const classeId = classeSelect.value;
                const anneeId = anneeSelect.value;
                let selectedOptionStillVisible = true;

                options.forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    const visible = option.dataset.classe === classeId && option.dataset.annee === anneeId;
                    option.hidden = !visible;
                    option.disabled = !visible;

                    if (option.selected && !visible) {
                        selectedOptionStillVisible = false;
                    }
                });

                if (!selectedOptionStillVisible) {
                    planificationSelect.value = '';
                }
            };

            classeSelect.addEventListener('change', filterPlanifications);
            anneeSelect.addEventListener('change', filterPlanifications);
            filterPlanifications();
        });

        // Niveau (numero de classe) de l'examen final du pays de l'ecole active
        // (ex: 12 = BAC au Mali) -- null si ce pays n'a pas d'examen national
        // configure, auquel cas une classe sans classe suivante est toujours
        // traitee comme une configuration manquante (branche "warning" ci-dessous).
        const examenFinalGrade = @json($examenFinalGrade);
        const reinscriptionI18n = @json($reinscriptionI18n);

        const reinscriptionForm = document.querySelector('[data-reinscription-form]');
        const sourceClassSelect = document.querySelector('[data-reinscription-source-class]');
        const targetClassSelect = document.querySelector('[data-reinscription-target-class]');
        const classHelp = document.querySelector('[data-reinscription-class-help]');
        const sourceYearSelect = document.querySelector('[data-reinscription-source-year]');
        const targetYearSelect = document.querySelector('[data-reinscription-target-year]');
        const targetYearHelp = document.querySelector('[data-reinscription-target-help]');
        const prepareButton = document.querySelector('[data-reinscription-prepare]');
        const guidance = document.querySelector('[data-reinscription-guidance]');

        const updatePrepareState = () => {
            if (!prepareButton || !guidance) return;

            const missing = [];
            if (!sourceClassSelect?.value) missing.push(reinscriptionI18n.missingClasseActuelle);
            if (!sourceYearSelect?.value) missing.push(reinscriptionI18n.missingAnneeActuelle);
            if (!targetYearSelect?.value) missing.push(reinscriptionI18n.missingAnneeCible);
            // La classe cible des passants reste optionnelle : une classe
            // terminale (BAC, ou DEF sans 10e dans l'ecole) n'en a jamais et
            // le serveur sait deja s'en passer (suggestNextClasse()), donc on
            // ne doit pas bloquer la preparation de la liste pour autant.

            if (missing.length === 0) {
                prepareButton.classList.remove('d-none');
                guidance.className = 'alert alert-success border-0 py-2 px-3 mb-0';
                guidance.textContent = reinscriptionI18n.guidanceAllReady;
                return;
            }

            prepareButton.classList.add('d-none');
            guidance.className = 'alert alert-secondary border-0 py-2 px-3 mb-0';
            guidance.textContent = reinscriptionI18n.guidanceMissingPrefix + missing.join(', ') + '.';
        };

        const syncTargetClass = () => {
            if (!sourceClassSelect || !targetClassSelect) return;

            const selected = sourceClassSelect.options[sourceClassSelect.selectedIndex];
            const sourceLevel = Number(selected?.dataset.level || 0);
            if (!sourceLevel) {
                targetClassSelect.value = '';
                if (classHelp) {
                    classHelp.classList.remove('d-none');
                    classHelp.textContent = reinscriptionI18n.chooseSourceClassHelp;
                }
                updatePrepareState();
                return;
            }

            const expected = String(sourceLevel + 1);
            const target = Array.from(targetClassSelect.options).find((option) => option.dataset.level === expected);
            if (target) {
                targetClassSelect.value = target.value;
                if (classHelp) {
                    classHelp.className = 'alert alert-success py-2 px-3 mt-2 mb-0';
                    classHelp.textContent = reinscriptionI18n.classTargetAutoHelp;
                }
            } else if (classHelp) {
                targetClassSelect.value = '';
                if (examenFinalGrade && sourceLevel === examenFinalGrade) {
                    // Le niveau terminal (BAC au Mali) n'a jamais de classe
                    // suivante a creer, ce n'est pas une configuration manquante.
                    classHelp.className = 'alert alert-info py-2 px-3 mt-2 mb-0';
                    classHelp.textContent = reinscriptionI18n.terminalClassHelp;
                } else {
                    classHelp.className = 'alert alert-warning py-2 px-3 mt-2 mb-0';
                    classHelp.textContent = reinscriptionI18n.noNextClassHelp;
                }
            }

            updatePrepareState();
        };

        if (sourceYearSelect && targetYearSelect) {
            const restrictTargetYears = () => {
                const selected = sourceYearSelect.options[sourceYearSelect.selectedIndex];
                const sourceStart = Number(selected?.dataset.startYear || 0);

                Array.from(targetYearSelect.options).forEach((option) => {
                    if (!option.value) return;
                    const targetStart = Number(option.dataset.startYear || 0);
                    const allowed = sourceStart > 0 && targetStart > sourceStart;
                    option.disabled = !allowed;
                    option.hidden = !allowed;
                });

                const current = targetYearSelect.options[targetYearSelect.selectedIndex];
                if (current?.value && current.disabled) {
                    targetYearSelect.value = '';
                }
            };

            const syncTargetYear = () => {
                restrictTargetYears();
                const selected = sourceYearSelect.options[sourceYearSelect.selectedIndex];
                const sourceStart = Number(selected?.dataset.startYear || 0);
                if (!sourceStart) {
                    return;
                }

                const expected = String(sourceStart + 1);
                const target = Array.from(targetYearSelect.options).find((option) => option.dataset.startYear === expected);
                if (target) {
                    targetYearSelect.value = target.value;
                    if (targetYearHelp) {
                        targetYearHelp.textContent = reinscriptionI18n.targetYearAutoHelp;
                        targetYearHelp.className = 'alert alert-success py-2 px-3 mt-2 mb-0';
                    }
                } else if (targetYearHelp) {
                    targetYearSelect.value = '';
                    targetYearHelp.textContent = reinscriptionI18n.nextYearMissingHelp;
                    targetYearHelp.className = 'alert alert-warning py-2 px-3 mt-2 mb-0';
                }
                updatePrepareState();
            };

            sourceYearSelect.addEventListener('change', syncTargetYear);
            if (!targetYearSelect.value) {
                syncTargetYear();
            } else {
                restrictTargetYears();
            }
        }

        sourceClassSelect?.addEventListener('change', syncTargetClass);
        targetClassSelect?.addEventListener('change', updatePrepareState);
        targetYearSelect?.addEventListener('change', updatePrepareState);
        sourceYearSelect?.addEventListener('change', updatePrepareState);
        syncTargetClass();
        updatePrepareState();

        if (reinscriptionForm) {
            const checkAll = reinscriptionForm.querySelector('[data-reinscription-check-all]');
            const checks = Array.from(reinscriptionForm.querySelectorAll('[data-reinscription-check]'));

            const updateCheckAll = () => {
                const enabled = checks.filter((check) => !check.disabled);
                const checked = enabled.filter((check) => check.checked);
                if (!checkAll) return;
                checkAll.checked = enabled.length > 0 && checked.length === enabled.length;
                checkAll.indeterminate = checked.length > 0 && checked.length < enabled.length;
            };

            checkAll?.addEventListener('change', () => {
                checks.forEach((check) => {
                    if (!check.disabled) check.checked = checkAll.checked;
                });
                updateCheckAll();
            });

            checks.forEach((check) => check.addEventListener('change', updateCheckAll));

            reinscriptionForm.querySelectorAll('[data-reinscription-decision]').forEach((select) => {
                const row = select.closest('tr');
                const classSelect = row?.querySelector('[data-reinscription-classe]');
                const observation = row?.querySelector('[data-reinscription-observation]');
                const syncClass = () => {
                    if (!classSelect) return;
                    const decision = select.value;
                    if (decision === 'passant') {
                        classSelect.value = select.dataset.passClasse;
                        classSelect.disabled = false;
                    } else if (decision === 'redoublant') {
                        classSelect.value = select.dataset.sourceClasse;
                        classSelect.disabled = false;
                    } else if (['admis_sortant', 'diplome_sortant', 'en_attente_resultat'].includes(decision)) {
                        classSelect.value = '';
                        classSelect.disabled = true;
                    } else {
                        classSelect.value = select.dataset.sourceClasse;
                        classSelect.disabled = true;
                    }
                    if (observation) {
                        observation.required = ['ajourne', 'abandon', 'exclu'].includes(decision);
                        observation.placeholder = observation.required ? reinscriptionI18n.motifRequiredPlaceholder : reinscriptionI18n.motifPlaceholder;
                    }
                };
                select.addEventListener('change', syncClass);
                syncClass();
            });

            updateCheckAll();
        }
    </script>
@endsection
