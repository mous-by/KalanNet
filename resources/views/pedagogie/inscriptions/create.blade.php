@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Élèves</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('eleves.index') }}">Élèves</a></li>
                    <li class="breadcrumb-item active">Inscription</li>
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
                    <h5 class="mb-0 fw-bold">Inscription individuelle</h5>
                </div>
                <div class="card-body p-4 p-lg-5">
                    <form action="{{ route('inscriptions.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Prénom <span class="text-danger">*</span></label>
                                <input type="text" name="prenom_eleve" class="form-control rounded-3" value="{{ old('prenom_eleve') }}" placeholder="Prénom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom_eleve" class="form-control rounded-3" value="{{ old('nom_eleve') }}" placeholder="Nom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Date de naissance</label>
                                <input type="date" name="date_naissance" class="form-control rounded-3" value="{{ old('date_naissance') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Lieu de naissance</label>
                                <input type="text" name="lieu_naiss" class="form-control rounded-3" value="{{ old('lieu_naiss') }}" placeholder="Ex: Kayes">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Adresse / Quartier</label>
                                <input type="text" name="adresse_eleve" class="form-control rounded-3" value="{{ old('adresse_eleve') }}" placeholder="Quartier ou adresse">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Genre <span class="text-danger">*</span></label>
                                <select name="genre_eleve" class="form-select rounded-3" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="Masculin" @selected(old('genre_eleve') === 'Masculin')>Masculin</option>
                                    <option value="Féminin" @selected(old('genre_eleve') === 'Féminin')>Féminin</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Matricule</label>
                                <input type="text" name="matricule" class="form-control rounded-3" value="{{ old('matricule') }}" placeholder="Automatique si vide">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Date d'inscription</label>
                                <input type="date" name="date_inscription" class="form-control rounded-3" value="{{ old('date_inscription', now()->toDateString()) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Cas social</label>
                                <select name="cas_social" class="form-select rounded-3">
                                    @foreach(['normal' => 'Normal', 'Dipenser' => 'Dispensé', 'Malade' => 'Malade'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('cas_social', 'normal') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Classe <span class="text-danger">*</span></label>
                                <select name="id_classe" class="form-select rounded-3" required>
                                    <option value="">Sélectionner une classe...</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id_classe }}" @selected(old('id_classe') == $classe->id_classe)>
                                            {{ $classe->nom_classe }} - {{ $classe->ordreEnseignement }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Mode de paiement</label>
                                <select name="mode_paiement" class="form-select rounded-3">
                                    @foreach(['' => 'Non défini', 'Mensuel' => 'Mensuel', 'Trimestriel' => 'Trimestriel', 'Annuel' => 'Annuel'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('mode_paiement') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Année Scolaire <span class="text-danger">*</span></label>
                                <select name="id_annee" class="form-select rounded-3" required>
                                    <option value="">Choisir une année...</option>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id_anneeScolaire }}" @selected(old('id_annee') == $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Planification <span class="text-danger">*</span></label>
                                <select name="id_planification" class="form-select rounded-3" required>
                                    <option value="">Veuillez choisir</option>
                                    @foreach($planifications as $planification)
                                        <option value="{{ $planification->id_planification }}" @selected(old('id_planification') == $planification->id_planification)>
                                            {{ $planification->motif }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-uppercase">Photo</label>
                                <input type="file" name="image" class="form-control rounded-3" accept="image/*">
                            </div>

                            <div class="col-12">
                                <div class="card theme-card shadow-sm">
                                    <div class="card-header">
                                        <h6 class="mb-0 fw-bold">Parent déjà inscrit</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label">Parent</label>
                                                <select name="parent_id" class="form-select">
                                                    <option value="">Aucun rattachement maintenant</option>
                                                    @foreach($parents as $parent)
                                                        <option value="{{ $parent->id_parent }}" @selected(old('parent_id') == $parent->id_parent)>{{ $parent->nom_prenom_parent }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Lien de parenté</label>
                                                <select name="lien_parent" class="form-select">
                                                    @foreach(['Parent', 'Père', 'Mère', 'Frère', 'Sœur', 'Tuteur', 'Tutrice', 'Autre'] as $lien)
                                                        <option value="{{ $lien }}" @selected(old('lien_parent', 'Parent') === $lien)>{{ $lien }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Informer</label>
                                                <select name="informer" class="form-select">
                                                    <option value="Oui" @selected(old('informer', 'Oui') === 'Oui')>Oui</option>
                                                    <option value="Non" @selected(old('informer') === 'Non')>Non</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12 mt-5">
                                <hr class="my-4">
                                <div class="d-flex justify-content-between">
                                    <button type="reset" class="btn btn-light px-5">Réinitialiser</button>
                                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold">Valider l'inscription</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
