@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Évaluations</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('evaluations.index') }}">Liste des évaluations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Fiches de notes à imprimer</li>
                </ol>
            </nav>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="alert alert-info border-0 border-start border-info border-4 small">
        <i class="bi bi-info-circle me-1"></i>
        Pour les enseignants qui notent sur papier : imprimez une fiche par matière, remettez-la à l'enseignant, puis saisissez
        ses notes dans KalanNet (« Préparer une évaluation », puis « Saisir les notes »). Les élèves sont dans le même ordre
        sur la fiche et à l'écran.
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('evaluations.fiches-vierges') }}" class="row g-3" id="fiches-form">
                <div class="col-md-4">
                    <label class="form-label">Classe *</label>
                    <select class="form-select" name="id_classe" onchange="this.form.submit();">
                        <option value="">Choisir une classe</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id_classe }}" @selected($classe?->id_classe === $c->id_classe)>{{ $c->nom_classe }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Année scolaire *</label>
                    <select class="form-select" name="id_annee_scolaire">
                        @foreach($annees as $annee)
                            <option value="{{ $annee->id_anneeScolaire }}" @selected($choix['id_annee_scolaire'] === (int) $annee->id_anneeScolaire)>{{ $annee->annee }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Période (imprimée sur la fiche)</label>
                    <select class="form-select" name="periode">
                        <option value="">À remplir par l'enseignant</option>
                        <optgroup label="Trimestres">
                            @foreach($trimestres as $trimestre)
                                <option value="t{{ $trimestre->id_trimestre }}" @selected($choix['periode'] === 't'.$trimestre->id_trimestre)>{{ $trimestre->nom_trimestre }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Mois">
                            @foreach([9, 10, 11, 12, 1, 2, 3, 4, 5, 6] as $m)
                                <option value="m{{ $m }}" @selected($choix['periode'] === 'm'.$m)>{{ $moisOptions[$m] }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Colonnes de notes sur la fiche</label>
                    <div class="d-flex align-items-center gap-3">
                        <select class="form-select w-auto" name="devoirs">
                            @foreach([0, 1, 2, 3] as $n)
                                <option value="{{ $n }}" @selected($choix['devoirs'] === $n)>{{ $n === 0 ? 'Aucun devoir' : $n.' devoir'.($n > 1 ? 's' : '') }}</option>
                            @endforeach
                        </select>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="composition" value="1" id="avec-composition" @checked($choix['composition'])>
                            <label class="form-check-label" for="avec-composition">+ Composition</label>
                        </div>
                    </div>
                    <div class="form-text">Ex. : Devoir 1, Devoir 2, Composition sur la même fiche.</div>
                </div>

                <div class="col-12">
                    <label class="form-label d-block">Matières * <span class="text-muted small">(une fiche par matière)</span></label>
                    @if(!$classe)
                        <div class="text-muted small">Choisissez d'abord une classe.</div>
                    @elseif($lignes->isEmpty())
                        <div class="text-muted small">Aucune matière n'est attribuée à cette classe.</div>
                    @else
                        <div class="mb-2 small">
                            <a href="#" onclick="document.querySelectorAll('.matiere-fiche').forEach(c => c.checked = true); return false;">Tout cocher</a> ·
                            <a href="#" onclick="document.querySelectorAll('.matiere-fiche').forEach(c => c.checked = false); return false;">Tout décocher</a>
                        </div>
                        <div class="row">
                            @foreach($lignes as $ligne)
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input matiere-fiche" type="checkbox" name="matieres[]" value="{{ $ligne->id_matiere }}" id="matiere-{{ $ligne->id_matiere }}" checked>
                                        <label class="form-check-label" for="matiere-{{ $ligne->id_matiere }}">
                                            {{ $ligne->matiere->nom_matiere }}
                                            <span class="text-muted small">— {{ $ligne->enseignant?->nom_prenom_enseignant ?? 'sans enseignant' }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="col-12 text-end">
                    <a href="{{ route('evaluations.index') }}" class="btn btn-light">Retour</a>
                    <button type="submit" name="format" value="xlsx" class="btn btn-outline-success" formaction="{{ route('evaluations.fiches-vierges.pdf') }}" @disabled(!$classe || $lignes->isEmpty()) title="Un classeur Excel : une feuille par matière, à imprimer ou à remplir à l'ordinateur">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </button>
                    <button type="submit" name="format" value="zip" class="btn btn-outline-primary" formaction="{{ route('evaluations.fiches-vierges.pdf') }}" @disabled(!$classe || $lignes->isEmpty()) title="Un fichier PDF séparé pour chaque matière, réunis dans un .zip">
                        <i class="bi bi-file-earmark-zip me-1"></i>Un fichier par matière (.zip)
                    </button>
                    <button type="submit" name="format" value="pdf" class="btn btn-primary" formaction="{{ route('evaluations.fiches-vierges.pdf') }}" formtarget="_blank" @disabled(!$classe || $lignes->isEmpty()) title="Un seul PDF : chaque matière sur sa propre page">
                        <i class="bi bi-printer me-1"></i>Imprimer toutes les fiches
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
