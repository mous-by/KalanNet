@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Élèves</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste des élèves inscrits</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filters (Disposition Alliance-Team) -->
    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0 fw-bold">Liste des élèves inscrits</h5>
            </div>
            <div class="card-body p-4">
                <p class="mb-2 fw-bold text-muted">Filtré par</p>
                <form action="{{ route('eleves.index') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label" for="id_classe">Classe</label>
                        <select class="form-select" id="id_classe" name="id_classe" onchange="this.form.submit()">
                            <option value="">Toutes les classes</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id_classe }}" {{ request('id_classe') == $classe->id_classe ? 'selected' : '' }}>
                                    {{ $classe->nom_classe }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="id_annee">Année Scolaire</label>
                        <select class="form-select" id="id_annee" name="id_annee" onchange="this.form.submit()">
                            <option value="">Toutes les années</option>
                            @foreach($annees as $annee)
                                <option value="{{ $annee->id_anneeScolaire }}" {{ request('id_annee') == $annee->id_anneeScolaire ? 'selected' : '' }}>
                                    {{ $annee->annee }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label" for="search_eleve">Recherche</label>
                        <div class="input-group">
                            <input type="text" name="search" id="search_eleve" placeholder="Tapez nom, prénom ou matricule..." class="form-control" value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Actions (Disposition Alliance-Team) -->
    <div class="row align-items-center mb-3">
        <div class="col-md-6 pt-2">
            <button type="button" class="btn btn-primary w-100">
                <i class="bi bi-printer me-2"></i> Imprimer la liste des élèves
            </button>
        </div>
        <div class="col-md-6 pt-2">
            <button type="button" class="btn btn-success w-100">
                <i class="bi bi-file-earmark-excel me-2"></i> Exporter la liste des élèves
            </button>
        </div>
    </div>

    <!-- Main Card (Disposition Alliance-Team) -->
    <div class="card theme-card shadow-sm mt-3">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div class="ms-auto">
                    <a href="{{ route('inscriptions.create') }}" class="btn px-4 theme-pill-active">
                        <i class="bi bi-plus-lg me-2"></i>Ajouter
                    </a>
                    <a href="{{ route('inscriptions.group.create') }}" class="btn btn-primary px-4 ms-2">
                        <i class="bi bi-people me-2"></i>Groupe
                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-3">
                <!-- Nav pills -->
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-2 theme-pill-active">
                            <i class="bi bi-list-task me-2"></i>Liste Inscription
                        </button>
                    </li>
                </ul>

                <div class="col-md-4">
                    <form action="{{ route('eleves.index') }}" method="POST">
                        @csrf
                        <input type="text" name="search" id="search_eleve" placeholder="Tapez nom, prénom ou matricule..." class="form-control" value="{{ request('search') }}">
                    </form>
                </div>
            </div>

            @if($showList)
                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th class="text-center"><input type="checkbox" id="check_all"></th>
                                <th>N°</th>
                                <th>Prénom & Nom</th>
                                <th>Date d'Inscription</th>
                                <th>Matricule</th>
                                <th>Genre</th>
                                <th>Photo</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($eleves as $index => $eleve)
                                <tr>
                                    <td class="text-center"><input type="checkbox" class="check_eleve" value="{{ $eleve->id_eleve }}"></td>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <h6 class="mb-0 fw-bold">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</h6>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($eleve->date_inscription)->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-light text-dark font-monospace">{{ $eleve->matricule }}</span></td>
                                    <td>
                                        @if($eleve->genre_eleve == 'Masculin')
                                            <span class="text-primary"><i class="bi bi-gender-male me-1"></i> Masculin</span>
                                        @else
                                            <span class="text-pink"><i class="bi bi-gender-female me-1"></i> Féminin</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="avatar-sm bg-light rounded-circle mx-auto d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            @if($eleve->image)
                                                <img src="{{ asset($eleve->image) }}" class="rounded-circle w-100 h-100 object-fit-cover">
                                            @else
                                                <i class="bi bi-person text-muted"></i>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('eleves.show', $eleve->id_eleve) }}" class="btn btn-light btn-sm p-2 me-1" title="Voir Profil"><i class="bi bi-eye text-info"></i></a>
                                            <button class="btn btn-light btn-sm p-2 me-1" title="Modifier"><i class="bi bi-pencil text-warning"></i></button>
                                            <button class="btn btn-light btn-sm p-2" title="Supprimer"><i class="bi bi-trash text-danger"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Aucun élève trouvé pour cette classe et cette année scolaire.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($eleves->hasPages())
                    <div class="mt-4">
                        {{ $eleves->links() }}
                    </div>
                @endif
            @else
                <div class="alert alert-info border-0 border-start border-info border-4 mt-3">
                    <h6 class="mb-2">Aucun résultat par défaut</h6>
                    <p class="mb-0">Sélectionnez d’abord une classe et une année scolaire pour afficher la liste des élèves inscrits.</p>
                </div>
            @endif
        </div>
    </div>

    <style>
        .text-pink { color: #ec4899; }
    </style>
@endsection
