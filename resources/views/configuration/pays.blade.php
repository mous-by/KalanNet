@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Pays</li>
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
                    <h5 class="mb-0 fw-bold"><i class="bi bi-globe-americas me-2"></i>Examens nationaux par pays</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 border-start border-info border-4">
                        Ces informations décrivent le système scolaire d'un pays (à quelle classe et sous quel nom
                        chaque examen national a lieu). Elles s'appliquent à <strong>toutes les écoles de ce pays</strong>,
                        pas seulement à la vôtre — vous êtes le mieux placé pour les connaître, contrairement à une
                        configuration centrale qui ne peut pas deviner le système de chaque pays.
                    </div>

                    @if($paysListe->isNotEmpty())
                        <form method="GET" action="{{ route('configuration.pays') }}" class="row g-2 mb-4" data-auto-filter="true">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold text-uppercase">Pays (SupAdmin)</label>
                                <select name="id_pays" class="form-select">
                                    @foreach($paysListe as $unPays)
                                        <option value="{{ $unPays->id }}" @selected($unPays->id === $pays->id)>{{ $unPays->nom }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    @else
                        <p class="text-muted mb-4">Pays de votre école : <strong>{{ $pays->nom }}</strong></p>
                    @endif

                    <form method="POST" action="{{ route('configuration.pays.update', $pays->id) }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-12">
                            <h6 class="fw-bold text-uppercase small text-muted">Examen intermédiaire (ex : DEF au Mali)</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Classe (numéro)</label>
                            <input type="number" name="niveau_examen_intermediaire" class="form-control" min="1" max="20"
                                   value="{{ old('niveau_examen_intermediaire', $pays->niveau_examen_intermediaire) }}" placeholder="Ex : 9">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom de l'examen</label>
                            <input type="text" name="nom_examen_intermediaire" class="form-control" maxlength="30"
                                   value="{{ old('nom_examen_intermediaire', $pays->nom_examen_intermediaire) }}" placeholder="Ex : DEF, BEPC...">
                        </div>

                        <div class="col-12 mt-4">
                            <h6 class="fw-bold text-uppercase small text-muted">Examen final (ex : BAC au Mali)</h6>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Classe (numéro)</label>
                            <input type="number" name="niveau_examen_final" class="form-control" min="1" max="20"
                                   value="{{ old('niveau_examen_final', $pays->niveau_examen_final) }}" placeholder="Ex : 12">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom de l'examen</label>
                            <input type="text" name="nom_examen_final" class="form-control" maxlength="30"
                                   value="{{ old('nom_examen_final', $pays->nom_examen_final) }}" placeholder="Ex : BAC">
                        </div>

                        <div class="col-12">
                            <div class="form-text">Laissez les deux champs d'un examen vides s'il n'y a pas d'examen national à ce niveau — les écoles concernées basculeront alors sur une décision de passage par moyenne.</div>
                        </div>

                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check2-circle me-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
