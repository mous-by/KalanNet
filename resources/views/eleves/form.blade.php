@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Élèves</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('eleves.index') }}">Liste des élèves</a></li>
                    <li class="breadcrumb-item active">Modifier</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold">Modifier l'élève</h5>
        </div>
        <div class="card-body p-4 p-lg-5">
            <form action="{{ route('eleves.update', $eleve->id_eleve) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Prénom <span class="text-danger">*</span></label>
                        <input type="text" name="prenom_eleve" class="form-control" value="{{ old('prenom_eleve', $eleve->prenom_eleve) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom_eleve" class="form-control" value="{{ old('nom_eleve', $eleve->nom_eleve) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Matricule</label>
                        <input type="text" name="matricule" class="form-control" value="{{ old('matricule', $eleve->matricule) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Genre <span class="text-danger">*</span></label>
                        <select name="genre_eleve" class="form-select" required>
                            <option value="Masculin" @selected(old('genre_eleve', $eleve->genre_eleve) === 'Masculin')>Masculin</option>
                            <option value="Féminin" @selected(old('genre_eleve', $eleve->genre_eleve) === 'Féminin')>Féminin</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Date de naissance</label>
                        <input type="date" name="date_naissance" class="form-control" value="{{ old('date_naissance', $eleve->date_naissance) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Lieu de naissance</label>
                        <input type="text" name="lieu_naiss" class="form-control" value="{{ old('lieu_naiss', $eleve->lieu_naiss) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Adresse</label>
                        <input type="text" name="adresse_eleve" class="form-control" value="{{ old('adresse_eleve', $eleve->adresse_eleve) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Classe <span class="text-danger">*</span></label>
                        <select name="id_classe" class="form-select" required>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" @selected(old('id_classe', $eleve->id_classe) == $classe->id_classe)>{{ $classe->nom_classe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Année scolaire <span class="text-danger">*</span></label>
                        <select name="id_annee" class="form-select" required>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" @selected(old('id_annee', $eleve->id_annee) == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Cas social</label>
                        <select name="cas_social" class="form-select">
                            @foreach(['normal' => 'Normal', 'Dipenser' => 'Dispensé', 'Malade' => 'Malade'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('cas_social', $eleve->cas_social) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Mode de paiement</label>
                        <select name="mode_paiement" class="form-select">
                            @foreach(['' => 'Non défini', 'Mensuel' => 'Mensuel', 'Trimestriel' => 'Trimestriel', 'Annuel' => 'Annuel'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('mode_paiement', $eleve->mode_paiement) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-uppercase">Date d'inscription</label>
                        <input type="date" name="date_inscription" class="form-control" value="{{ old('date_inscription', $eleve->date_inscription) }}">
                    </div>
                    <div class="col-12 d-flex justify-content-between mt-4">
                        <a href="{{ route('eleves.index') }}" class="btn btn-light px-4">Retour</a>
                        <button type="submit" class="btn btn-primary px-5 fw-bold">Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
