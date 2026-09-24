@extends('layouts.app')

@section('content')
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

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card theme-card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0 fw-bold">{{ __('inscriptions.individual_title') }}</h5>
                </div>
                <div class="card-body p-4 p-lg-5">
                    <form action="{{ route('inscriptions.store') }}" method="POST" enctype="multipart/form-data">
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
                                <select name="id_classe" class="form-select rounded-3" required>
                                    <option value="">{{ __('inscriptions.select_classe') }}</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id_classe }}" @selected(old('id_classe') == $classe->id_classe)>
                                            {{ $classe->nom_classe }} - {{ $classe->ordreEnseignement }}
                                        </option>
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
                                <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_annee') }} <span class="text-danger">*</span></label>
                                <select name="id_annee" class="form-select rounded-3" required>
                                    <option value="">{{ __('eleves.choose_annee') }}</option>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id_anneeScolaire }}" @selected(old('id_annee') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">{{ __('inscriptions.formule_paiement_label') }} <span class="text-danger">*</span></label>
                                <select name="id_planification" class="form-select rounded-3" required>
                                    <option value="">{{ __('inscriptions.select_planification') }}</option>
                                    @foreach($planifications as $planification)
                                        <option value="{{ $planification->id_planification }}" @selected(old('id_planification') == $planification->id_planification)>
                                            {{ $planification->motif }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F
                                        </option>
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
    </div>
@endsection
