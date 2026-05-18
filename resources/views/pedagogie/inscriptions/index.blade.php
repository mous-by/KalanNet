@extends('layouts.app')

@section('content')
@php
    $activeTab = request('tab', 'individual');
@endphp

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
                    <a class="nav-link {{ $activeTab === 'individual' ? 'active theme-pill-active' : '' }}" href="{{ route('inscriptions.index', ['tab' => 'individual']) }}">Inscription individuelle</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'group' ? 'active theme-pill-active' : '' }}" href="{{ route('inscriptions.index', ['tab' => 'group']) }}">Inscription par groupe</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link {{ $activeTab === 'reinscription' ? 'active theme-pill-active' : '' }}" href="{{ route('inscriptions.index', ['tab' => 'reinscription']) }}">Réinscription</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'individual' ? 'show active' : '' }}" id="tab-individual" role="tabpanel">
                    <div class="card border-0">
                        <div class="card-header bg-white">
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
                                                <option value="{{ $classe->id_classe }}" @selected(old('id_classe') == $classe->id_classe)>{{ $classe->nom_classe }} - {{ $classe->ordreEnseignement }}</option>
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
                                        <label class="form-label small fw-bold text-uppercase">Année scolaire <span class="text-danger">*</span></label>
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
                                                <option value="{{ $planification->id_planification }}" @selected(old('id_planification') == $planification->id_planification)>{{ $planification->motif }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F</option>
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

                <div class="tab-pane fade {{ $activeTab === 'group' ? 'show active' : '' }}" id="tab-group" role="tabpanel">
                    <div class="card theme-card border-0 shadow-sm">
                        <div class="card-header theme-header border-0">
                            <h5 class="mb-0 fw-bold">Inscription par groupe</h5>
                        </div>
                        <div class="card-body p-4 p-lg-5">
                            <form action="{{ route('inscriptions.group.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Fichier Excel <span class="text-danger">*</span></label>
                                        <input type="file" name="fichier_excel" class="form-control rounded-3" accept=".xls,.xlsx" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Classe <span class="text-danger">*</span></label>
                                        <select name="id_classe" class="form-select rounded-3" required>
                                            <option value="">Sélectionner une classe...</option>
                                            @foreach($classes as $classe)
                                                <option value="{{ $classe->id_classe }}">{{ $classe->nom_classe }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Année scolaire <span class="text-danger">*</span></label>
                                        <select name="id_annee" class="form-select rounded-3" required>
                                            <option value="">Choisir une année...</option>
                                            @foreach($annees as $annee)
                                                <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Planification <span class="text-danger">*</span></label>
                                        <select name="id_planification" class="form-select rounded-3" required>
                                            <option value="">Veuillez choisir</option>
                                            @foreach($planifications as $planification)
                                                <option value="{{ $planification->id_planification }}">{{ $planification->motif }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Date d'inscription</label>
                                        <input type="date" name="date_inscription" class="form-control rounded-3" value="{{ now()->toDateString() }}">
                                    </div>
                                    <div class="col-12 text-end mt-3">
                                        <button type="submit" class="btn theme-pill-active px-5 py-2 fw-bold">Importer et inscrire</button>
                                    </div>
                                </div>
                            </form>

                            <div class="mt-5 card theme-card shadow-sm border-0">
                                <div class="card-body p-4">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                                        <div>
                                            <h6 class="mb-2">Modèle Excel d'inscription par groupe</h6>
                                            <p class="mb-2">Téléchargez le modèle officiel et remplissez-le avec les colonnes attendues. Les lignes vides seront ignorées.</p>
                                            <a href="{{ route('inscriptions.group.template') }}" class="btn btn-outline-primary">Télécharger le modèle Excel</a>
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
                            <h5 class="mb-0 fw-bold">Réinscription</h5>
                        </div>
                        <div class="card-body p-4 p-lg-5">
                            <div class="alert alert-info border-0 border-start border-info border-4">
                                <h6 class="mb-2">Créer une réinscription</h6>
                                <p class="mb-2">Cette action crée un enregistrement dans les tables <strong>reinscription</strong> et <strong>ligne_reinscription</strong>, puis met à jour la classe et l'année scolaire de l'élève.</p>
                            </div>
                            <form action="{{ route('inscriptions.reinscription.store') }}" method="POST">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Élève <span class="text-danger">*</span></label>
                                        <select name="id_eleve" class="form-select rounded-3" required>
                                            <option value="">Sélectionner un élève...</option>
                                            @foreach($eleves as $eleve)
                                                <option value="{{ $eleve->id_eleve }}">
                                                    {{ $eleve->nom_eleve }} {{ $eleve->prenom_eleve }} @if($eleve->classe) - {{ $eleve->classe->nom_classe }}@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Nouvelle classe <span class="text-danger">*</span></label>
                                        <select name="id_classe" class="form-select rounded-3" required>
                                            <option value="">Choisir la classe cible...</option>
                                            @foreach($classes as $classe)
                                                <option value="{{ $classe->id_classe }}">{{ $classe->nom_classe }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Nouvelle année scolaire <span class="text-danger">*</span></label>
                                        <select name="id_annee" class="form-select rounded-3" required>
                                            <option value="">Choisir une année...</option>
                                            @foreach($annees as $annee)
                                                <option value="{{ $annee->id_anneeScolaire }}">{{ $annee->annee }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Statut de réinscription <span class="text-danger">*</span></label>
                                        <select name="statut" class="form-select rounded-3" required>
                                            <option value="passant">Passant</option>
                                            <option value="redoublant">Redoublant</option>
                                            <option value="non défini">Non défini</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Date de réinscription</label>
                                        <input type="date" name="date_reinscription" class="form-control rounded-3" value="{{ now()->toDateString() }}">
                                    </div>
                                    <div class="col-12 mt-3 text-end">
                                        <button type="submit" class="btn theme-pill-active px-5 py-2 fw-bold">Valider la réinscription</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
