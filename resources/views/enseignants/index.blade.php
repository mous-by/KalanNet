@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Enseignants</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Corps Enseignant</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Filters (Disposition Alliance-Team) -->
    <div class="col-12">
        <div class="card theme-card shadow-sm">
            <div class="card-body p-4">
                <p class="mb-2 fw-bold text-muted">Filtrer par</p>
                <form action="{{ route('enseignants.search') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-12">
                        <label class="form-label" for="search">Recherche rapide</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="Nom, email, téléphone ou matricule..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary px-4 ms-2 rounded-3">Rechercher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Actions (Disposition Alliance-Team) -->
    <div class="row align-items-center mb-3">
        <div class="col-md-12 pt-2 text-end">
            <button type="button" class="btn px-4 theme-pill-active">
                <i class="bi bi-printer me-2"></i> Imprimer la liste des enseignants
            </button>
        </div>

    </div>

    <!-- Main Card (Disposition Alliance-Team) -->
    <div class="card theme-card shadow-sm mt-3">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
            @endif
            <div class="d-flex align-items-center mb-3">
                <div class="ms-auto">
                    <a href="{{ route('enseignants.create') }}" class="btn px-4 theme-pill-active">
                        <i class="bi bi-plus-lg me-2"></i>Ajouter

                    </a>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-3">
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-2 theme-pill-active">
                            <i class="bi bi-person-badge me-2"></i>Liste Enseignants
                        </button>
                    </li>
                </ul>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th class="text-center"><input type="checkbox" id="check_all"></th>
                            <th>N°</th>
                            <th>Prénom & Nom</th>
                            <th>Contact</th>
                            <th>Type Contrat</th>
                            <th>Spécialité</th>
                            <th>Statut</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enseignants as $index => $enseignant)
                            <tr>
                                <td class="text-center"><input type="checkbox" class="check_enseignant" value="{{ $enseignant->id_enseignant }}"></td>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-light rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            @if($enseignant->avatar_enseignant)
                                                <img src="{{ asset($enseignant->avatar_enseignant) }}" class="rounded-circle w-100 h-100 object-fit-cover">
                                            @else
                                                <i class="bi bi-person text-muted"></i>
                                            @endif
                                        </div>
                                        <h6 class="mb-0 fw-bold">{{ $enseignant->nom_prenom_enseignant }}</h6>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div class="text-dark"><i class="bi bi-envelope me-1"></i> {{ $enseignant->email_enseignant }}</div>
                                        <div class="text-muted"><i class="bi bi-phone me-1"></i> {{ $enseignant->telephone_enseignant }}</div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-primary border border-primary-subtle">{{ $enseignant->type_contrat_enseignant }}</span></td>
                                <td>{{ $enseignant->specialite ?: 'N/A' }}</td>
                                <td>
                                    @if($enseignant->is_deleted == 0)
                                        <span class="badge bg-success-soft text-success px-2 py-1">Actif</span>
                                    @else
                                        <span class="badge bg-danger-soft text-danger px-2 py-1">Archivé</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('enseignants.show', $enseignant->id_enseignant) }}" class="btn btn-light btn-sm p-2 me-1" title="Voir Profil"><i class="bi bi-eye text-info"></i></a>
                                        @if($enseignant->is_deleted == 0)
                                            <a href="{{ route('enseignants.edit', $enseignant->id_enseignant) }}" class="btn btn-light btn-sm p-2 me-1" title="Modifier"><i class="bi bi-pencil text-warning"></i></a>
                                            <form action="{{ route('enseignants.archive', $enseignant->id_enseignant) }}" method="POST" onsubmit="return confirm('Archiver cet enseignant ?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-light btn-sm p-2" title="Archiver"><i class="bi bi-archive text-danger"></i></button>
                                            </form>
                                        @else
                                            <form action="{{ route('enseignants.reactivate', $enseignant->id_enseignant) }}" method="POST" onsubmit="return confirm('Réactiver cet enseignant ?');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-light btn-sm p-2" title="Réactiver"><i class="bi bi-arrow-clockwise text-success"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Aucun enseignant trouvé.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($enseignants->hasPages())
                <div class="mt-4">
                    {{ $enseignants->links() }}
                </div>
            @endif
        </div>
    </div>

    <style>
        .bg-success-soft { background-color: rgba(var(--bs-success-rgb), 0.1) !important; }
        .bg-danger-soft { background-color: rgba(var(--bs-danger-rgb), 0.1) !important; }
    </style>
@endsection
