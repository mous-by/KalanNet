@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('configuration.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">{{ __('configuration.menu_apercu') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('configuration.menu_pays') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('configuration.partials.flash')

    <div class="row g-4">
        <div class="col-12 col-lg-3">@include('configuration._menu')</div>
        <div class="col-12 col-lg-9">
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-globe-americas me-2"></i>{{ __('configuration.pays_examens_title') }}</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 border-start border-info border-4">
                        {{ __('configuration.pays_intro') }}
                    </div>

                    <p class="text-muted mb-4">{{ __('configuration.pays_ecole_pays') }} <strong>{{ $pays->nom }}</strong></p>

                    <form method="POST" action="{{ route('configuration.pays.update', $pays->id) }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-12">
                            <h6 class="fw-bold text-uppercase small text-muted">{{ __('configuration.pays_examen_primaire_title') }}</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('configuration.pays_classe_numero') }}</label>
                            <input type="number" name="niveau_examen_primaire" class="form-control" min="1" max="20"
                                   value="{{ old('niveau_examen_primaire', $pays->niveau_examen_primaire) }}" placeholder="{{ __('configuration.pays_ex_6') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('configuration.pays_nom_examen') }}</label>
                            <input type="text" name="nom_examen_primaire" class="form-control" maxlength="30"
                                   value="{{ old('nom_examen_primaire', $pays->nom_examen_primaire) }}" placeholder="{{ __('configuration.pays_ex_cepe') }}">
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-muted">{{ __('configuration.pays_examen_intermediaire_title') }}</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('configuration.pays_classe_numero') }}</label>
                            <input type="number" name="niveau_examen_intermediaire" class="form-control" min="1" max="20"
                                   value="{{ old('niveau_examen_intermediaire', $pays->niveau_examen_intermediaire) }}" placeholder="{{ __('configuration.pays_ex_9') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('configuration.pays_nom_examen') }}</label>
                            <input type="text" name="nom_examen_intermediaire" class="form-control" maxlength="30"
                                   value="{{ old('nom_examen_intermediaire', $pays->nom_examen_intermediaire) }}" placeholder="{{ __('configuration.pays_ex_def') }}">
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-muted">{{ __('configuration.pays_examen_final_title') }}</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('configuration.pays_classe_numero') }}</label>
                            <input type="number" name="niveau_examen_final" class="form-control" min="1" max="20"
                                   value="{{ old('niveau_examen_final', $pays->niveau_examen_final) }}" placeholder="{{ __('configuration.pays_ex_12') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('configuration.pays_nom_examen') }}</label>
                            <input type="text" name="nom_examen_final" class="form-control" maxlength="30"
                                   value="{{ old('nom_examen_final', $pays->nom_examen_final) }}" placeholder="{{ __('configuration.pays_ex_bac') }}">
                        </div>

                        <div class="col-12">
                            <div class="form-text">{{ __('configuration.pays_champs_vides_help') }}</div>
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-muted">{{ __('configuration.pays_entete_title') }}</h6>
                        </div>
                        <div class="col-12">
                            <div class="form-text mb-2">{{ __('configuration.pays_entete_help') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('configuration.pays_entete_gauche_label') }}</label>
                            <textarea name="entete_document_gauche" class="form-control" rows="2" maxlength="200" placeholder="Ex : MINISTERE DE L'EDUCATION NATIONALE">{{ old('entete_document_gauche', $pays->entete_document_gauche) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('configuration.pays_entete_droite_label') }}</label>
                            <textarea name="entete_document_droite" class="form-control" rows="2" maxlength="200" placeholder="Ex : REPUBLIQUE DU MALI&#10;UN PEUPLE - UN BUT - UNE FOI">{{ old('entete_document_droite', $pays->entete_document_droite) }}</textarea>
                        </div>

                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check2-circle me-2"></i>{{ __('configuration.enregistrer') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
