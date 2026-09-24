@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('eleves.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('eleves.index') }}">{{ __('eleves.list_link') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('eleves.breadcrumb_edit') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold">{{ __('eleves.edit_title') }}</h5>
        </div>
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('eleves.update', $eleve->id_eleve) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_prenom') }} <span class="text-danger">*</span></label>
                        <input type="text" name="prenom_eleve" class="form-control" value="{{ old('prenom_eleve', $eleve->prenom_eleve) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_nom') }} <span class="text-danger">*</span></label>
                        <input type="text" name="nom_eleve" class="form-control" value="{{ old('nom_eleve', $eleve->nom_eleve) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_matricule') }}</label>
                        <input type="text" name="matricule" class="form-control" value="{{ old('matricule', $eleve->matricule) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_genre') }} <span class="text-danger">*</span></label>
                        <select name="genre_eleve" class="form-select" required>
                            <option value="Masculin" @selected(old('genre_eleve', $eleve->genre_eleve) === 'Masculin')>{{ __('eleves.genre_masculin') }}</option>
                            <option value="Féminin" @selected(old('genre_eleve', $eleve->genre_eleve) === 'Féminin')>{{ __('eleves.genre_feminin') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_date_naissance') }}</label>
                        <input type="date" name="date_naissance" class="form-control" value="{{ old('date_naissance', $eleve->date_naissance) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_lieu_naissance') }}</label>
                        <input type="text" name="lieu_naiss" class="form-control" value="{{ old('lieu_naiss', $eleve->lieu_naiss) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_adresse') }}</label>
                        <input type="text" name="adresse_eleve" class="form-control" value="{{ old('adresse_eleve', $eleve->adresse_eleve) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_classe') }} <span class="text-danger">*</span></label>
                        <select name="id_classe" class="form-select" required>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" @selected(old('id_classe', $eleve->id_classe) == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_annee') }} <span class="text-danger">*</span></label>
                        <select name="id_annee" class="form-select" required>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected(old('id_annee', $eleve->id_annee) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.lv2_label') }}</label>
                        <select name="id_matiere_lv2" class="form-select">
                            <option value="">{{ __('eleves.lv2_none') }}</option>
                            @foreach($matieresLv2 as $matiereLv2)
                                <option value="{{ $matiereLv2->id_matiere }}" @selected(old('id_matiere_lv2', $eleve->id_matiere_lv2) == $matiereLv2->id_matiere)>
                                    {{ $matiereLv2->nom_matiere }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">{{ __('eleves.lv2_help') }}</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_cas_social') }}</label>
                        <select name="cas_social" class="form-select">
                            @foreach(['normal' => __('eleves.cas_social_normal'), 'Dipenser' => __('eleves.cas_social_dispense'), 'Malade' => __('eleves.cas_social_malade')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('cas_social', $eleve->cas_social) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_mode_paiement') }}</label>
                        <select name="mode_paiement" class="form-select">
                            @foreach(['' => __('eleves.mode_paiement_non_defini'), 'Mensuel' => __('eleves.mode_paiement_mensuel'), 'Trimestriel' => __('eleves.mode_paiement_trimestriel'), 'Annuel' => __('eleves.mode_paiement_annuel')] as $value => $label)
                                <option value="{{ $value }}" @selected(old('mode_paiement', $eleve->mode_paiement) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_statut_paiement') }}</label>
                        <select name="statut_paiement" class="form-select">
                            @foreach([
                                'normal' => __('eleves.statut_normal'),
                                'subventionne' => __('eleves.statut_subventionne'),
                                'boursier' => __('eleves.statut_boursier'),
                                'gratuit' => __('eleves.statut_gratuit'),
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('statut_paiement', $eleve->statut_paiement ?? 'normal') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">{{ __('eleves.label_date_inscription') }}</label>
                        <input type="date" name="date_inscription" class="form-control" value="{{ old('date_inscription', $eleve->date_inscription) }}">
                    </div>
                    <div class="col-12 d-flex justify-content-between mt-4">
                        <a href="{{ route('eleves.index') }}" class="btn btn-light px-4">{{ __('eleves.back') }}</a>
                        <button type="submit" class="btn btn-primary px-5 fw-bold">{{ __('eleves.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
