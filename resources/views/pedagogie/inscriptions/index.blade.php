@extends('layouts.app')

@section('content')
@php
    $activeTab = request('tab', 'individual');
    $planificationRequired = $planificationRequired ?? true;
    $planificationLabel = $planificationLabel ?? 'Planification';
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
                            <form action="{{ route('inscriptions.store') }}" method="POST" enctype="multipart/form-data" data-planification-form>
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
                                        <select name="id_classe" class="form-select rounded-3" required data-planification-classe>
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
                                        <select name="id_annee" class="form-select rounded-3" required data-planification-annee>
                                            <option value="">Choisir une année...</option>
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
                                            <option value="">{{ $planificationRequired ? 'Veuillez choisir' : 'Sans coopérative / sans frais' }}</option>
                                            @foreach($planifications as $planification)
                                                <option value="{{ $planification->id_planification }}" data-classe="{{ $planification->id_classe }}" data-annee="{{ $planification->id_annee }}" @selected(old('id_planification') == $planification->id_planification)>{{ $planificationRequired ? $planification->motif : 'Coopérative' }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F</option>
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
                            <form action="{{ route('inscriptions.group.import') }}" method="POST" enctype="multipart/form-data" data-planification-form>
                                @csrf
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Fichier Excel <span class="text-danger">*</span></label>
                                        <input type="file" name="fichier_excel" class="form-control rounded-3" accept=".xls,.xlsx" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Classe <span class="text-danger">*</span></label>
                                        <select name="id_classe" class="form-select rounded-3" required data-planification-classe>
                                            <option value="">Sélectionner une classe...</option>
                                            @foreach($classes as $classe)
                                                <option value="{{ $classe->id_classe }}">{{ $classe->nom_classe }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Année scolaire <span class="text-danger">*</span></label>
                                        <select name="id_annee" class="form-select rounded-3" required data-planification-annee>
                                            <option value="">Choisir une année...</option>
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
                                            <option value="">{{ $planificationRequired ? 'Veuillez choisir' : 'Sans coopérative / sans frais' }}</option>
                                            @foreach($planifications as $planification)
                                                <option value="{{ $planification->id_planification }}" data-classe="{{ $planification->id_classe }}" data-annee="{{ $planification->id_annee }}">{{ $planificationRequired ? $planification->motif : 'Coopérative' }} - {{ number_format((float) $planification->montant_planification, 0, ',', ' ') }} F</option>
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
                            <h5 class="mb-0 fw-bold">Réinscription intelligente</h5>
                        </div>
                        <div class="card-body p-4 p-lg-5">
                            @php
                                $reinscriptionPreview = $reinscriptionPreview ?? null;
                                $reinscriptionFilters = $reinscriptionFilters ?? [];
                                $decisionLabels = [
                                    'passant' => 'Passant',
                                    'redoublant' => 'Redoublant',
                                    'admis_sortant' => 'Admis sortant',
                                    'diplome_sortant' => 'Diplômé sortant',
                                    'en_attente_resultat' => 'En attente résultat',
                                    'ajourne' => 'Ajourné / année blanche',
                                    'abandon' => 'Abandon',
                                    'exclu' => 'Exclu',
                                ];
                                $proposalLabels = [
                                    'passant' => 'Passant',
                                    'redoublant' => 'Redoublant',
                                    'non_defini' => 'Moyenne non disponible',
                                    'admis_sortant' => 'Admis sortant',
                                    'diplome_sortant' => 'Diplômé sortant',
                                    'en_attente_resultat' => 'Résultat national absent',
                                ];
                            @endphp

                            <div class="alert alert-info border-0 border-start border-info border-4">
                                <h6 class="mb-2">Assistant de réinscription par classe</h6>
                                <p class="mb-0">Choisissez la classe de départ et l'année cible. Le système propose automatiquement passant ou redoublant selon la moyenne annuelle, avec possibilité de corriger la décision.</p>
                            </div>

                            <form action="{{ route('inscriptions.reinscription.store') }}" method="POST" class="mb-4">
                                @csrf
                                <input type="hidden" name="preview_reinscription" value="1">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-uppercase">Classe actuelle <span class="text-danger">*</span></label>
                                        <select name="source_classe_id" class="form-select rounded-3" required data-reinscription-source-class>
                                            <option value="">Choisir la classe...</option>
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
                                        <label class="form-label small fw-bold text-uppercase">Année actuelle <span class="text-danger">*</span></label>
                                        <select name="source_annee_id" class="form-select rounded-3" required data-reinscription-source-year>
                                            <option value="">Choisir l'année...</option>
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
                                        <label class="form-label small fw-bold text-uppercase">Année cible <span class="text-danger">*</span></label>
                                        <select name="target_annee_id" class="form-select rounded-3" required data-reinscription-target-year>
                                            <option value="">Choisir l'année cible...</option>
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
                                        <label class="form-label small fw-bold text-uppercase">Classe cible des passants</label>
                                        <select name="target_classe_id" class="form-select rounded-3" data-reinscription-target-class>
                                            <option value="">Automatique si possible</option>
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
                                        <label class="form-label small fw-bold text-uppercase">Date de réinscription</label>
                                        <input type="date" name="date_reinscription" class="form-control rounded-3" value="{{ $reinscriptionFilters['date_reinscription'] ?? old('date_reinscription', now()->toDateString()) }}">
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-secondary border-0 py-2 px-3 mb-0" data-reinscription-guidance>
                                            Choisissez d’abord la classe actuelle et l’année actuelle. Le système proposera automatiquement l’année cible et la classe suivante si elles existent.
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn theme-pill-active w-100 py-2 fw-bold d-none" data-reinscription-prepare>Préparer la liste</button>
                                    </div>
                                </div>
                            </form>

                            @if($reinscriptionPreview)
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted">Classe source</div>
                                            <div class="fw-bold">{{ $reinscriptionPreview['sourceClasse']->nom_classe }}</div>
                                            @if($reinscriptionPreview['niveauExamen'])
                                                <div class="small">Examen : {{ $reinscriptionPreview['niveauExamen'] }}</div>
                                            @else
                                                <div class="small">Seuil : {{ number_format($reinscriptionPreview['seuil'], 2, ',', ' ') }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted">Classe proposée</div>
                                            <div class="fw-bold">{{ $reinscriptionPreview['targetClasse']?->nom_classe ?? 'Sortie / transfert' }}</div>
                                            <div class="small">{{ $reinscriptionPreview['targetAnnee']?->annee }}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted">Propositions</div>
                                            <div class="fw-bold">{{ $reinscriptionPreview['stats']['passants'] }} passant(s)</div>
                                            <div class="small">{{ $reinscriptionPreview['stats']['redoublants'] }} redoublant(s)</div>
                                            @if($reinscriptionPreview['niveauExamen'])
                                                <div class="small">{{ $reinscriptionPreview['stats']['sortants'] }} sortant(s)</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="border rounded-3 p-3 h-100">
                                            <div class="small text-muted">À surveiller</div>
                                            <div class="fw-bold">
                                                {{ $reinscriptionPreview['niveauExamen'] ? $reinscriptionPreview['stats']['en_attente_resultat'].' en attente résultat' : $reinscriptionPreview['stats']['sans_moyenne'].' sans moyenne' }}
                                            </div>
                                            <div class="small">{{ $reinscriptionPreview['stats']['deja_reinscrits'] }} déjà réinscrit(s)</div>
                                        </div>
                                    </div>
                                </div>

                                @if($reinscriptionPreview['rows']->isEmpty())
                                    <div class="alert alert-warning border-0 border-start border-warning border-4">
                                        Aucun élève actif trouvé pour cette classe et cette année scolaire.
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
                                                <h6 class="mb-1 fw-bold">Décisions à valider</h6>
                                                <div class="small text-muted">Les décisions proposées peuvent être changées avant validation.</div>
                                            </div>
                                            <button type="submit" class="btn btn-primary px-4 fw-bold">Valider les élèves cochés</button>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" style="width: 48px;"><input type="checkbox" class="form-check-input" data-reinscription-check-all checked></th>
                                                    <th>Élève</th>
                                                    <th>Matricule</th>
                                                    @if($reinscriptionPreview['niveauExamen'])
                                                        <th>Examen</th>
                                                        <th>Résultat national</th>
                                                        <th>Moyenne examen</th>
                                                    @else
                                                        <th>Moyenne annuelle</th>
                                                    @endif
                                                    <th>Proposition</th>
                                                    <th>Décision finale</th>
                                                    <th>Classe finale</th>
                                                    <th>Observation</th>
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
                                                                <span class="badge bg-warning text-dark">Déjà réinscrit</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $eleve->matricule ?: 'Non renseigné' }}</td>
                                                        @if($reinscriptionPreview['niveauExamen'])
                                                            <td>{{ $row['niveau_examen'] }}</td>
                                                            <td>{{ $row['resultat_national'] ?: 'Non disponible' }}</td>
                                                            <td>
                                                                @if($row['moyenne_examen'] === null)
                                                                    <span class="text-muted">Non disponible</span>
                                                                @else
                                                                    <span class="fw-bold">{{ number_format($row['moyenne_examen'], 2, ',', ' ') }}</span>
                                                                @endif
                                                            </td>
                                                        @else
                                                            <td>
                                                                @if($row['moyenne'] === null)
                                                                    <span class="text-muted">Non disponible</span>
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
                                                                <option value="" @selected($row['classe_cible_id'] === null)>Aucune classe</option>
                                                                @foreach($classes as $classe)
                                                                    <option value="{{ $classe->id_classe }}" @selected((int) $row['classe_cible_id'] === (int) $classe->id_classe)>{{ $classe->nom_classe }}</option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="eleves[{{ $key }}][motif_decision]" class="form-control form-control-sm" data-reinscription-observation placeholder="Motif si nécessaire" @disabled($row['deja_reinscrit'] || $blockedDecision)>
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
            if (!sourceClassSelect?.value) missing.push('la classe actuelle');
            if (!sourceYearSelect?.value) missing.push('l’année actuelle');
            if (!targetYearSelect?.value) missing.push('l’année cible');
            if (!targetClassSelect?.value) missing.push('la classe cible des passants');

            if (missing.length === 0) {
                prepareButton.classList.remove('d-none');
                guidance.className = 'alert alert-success border-0 py-2 px-3 mb-0';
                guidance.textContent = 'Toutes les conditions sont prêtes. Vous pouvez préparer la liste de réinscription.';
                return;
            }

            prepareButton.classList.add('d-none');
            guidance.className = 'alert alert-secondary border-0 py-2 px-3 mb-0';
            guidance.textContent = 'À compléter avant de préparer la liste : ' + missing.join(', ') + '.';
        };

        const syncTargetClass = () => {
            if (!sourceClassSelect || !targetClassSelect) return;

            const selected = sourceClassSelect.options[sourceClassSelect.selectedIndex];
            const sourceLevel = Number(selected?.dataset.level || 0);
            if (!sourceLevel) {
                targetClassSelect.value = '';
                if (classHelp) {
                    classHelp.classList.remove('d-none');
                    classHelp.textContent = 'Choisissez une classe actuelle pour que le système cherche la classe suivante.';
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
                    classHelp.textContent = 'Classe cible proposée automatiquement.';
                }
            } else if (classHelp) {
                targetClassSelect.value = '';
                classHelp.className = 'alert alert-warning py-2 px-3 mt-2 mb-0';
                classHelp.textContent = 'La classe suivante n’est pas encore créée. Créez-la dans Classes avant de préparer la réinscription.';
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
                        targetYearHelp.textContent = 'Année cible proposée automatiquement.';
                        targetYearHelp.className = 'alert alert-success py-2 px-3 mt-2 mb-0';
                    }
                } else if (targetYearHelp) {
                    targetYearSelect.value = '';
                    targetYearHelp.textContent = 'L’année suivante n’est pas encore créée. Créez-la dans Configuration > Années scolaires avant de valider une réinscription.';
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
                        observation.placeholder = observation.required ? 'Motif obligatoire' : 'Motif si nécessaire';
                    }
                };
                select.addEventListener('change', syncClass);
                syncClass();
            });

            updateCheckAll();
        }
    </script>
@endsection
