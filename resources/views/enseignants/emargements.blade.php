@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Enseignants</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('enseignants.index') }}">Enseignants</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Émargements</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            {{ $errors->first() }}
        </div>
    @endif

    @if($emargementPermissions['create'])
        <div class="mb-3 d-flex justify-content-end">
            <button class="btn px-4 theme-pill-active" data-bs-toggle="modal" data-bs-target="#createEmargementModal">
                <i class="bx bx-plus me-2"></i>Nouvel émargement
            </button>
        </div>
    @endif

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bx bx-filter-alt me-2"></i>Filtrer les émargements</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('enseignants.emargements.filter') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Enseignant</label>
                    <select name="id_enseignant" class="form-select">
                        <option value="">Tous</option>
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id_enseignant }}" @selected(request('id_enseignant') == $enseignant->id_enseignant)>
                                {{ $enseignant->nom_prenom_enseignant }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Classe</label>
                    <select name="id_classe" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" @selected(request('id_classe') == $classe->id_classe)>
                                {{ $classe->nom_classe }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Matière</label>
                    <select name="id_matiere" class="form-select">
                        <option value="">Toutes</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id_matiere }}" @selected(request('id_matiere') == $matiere->id_matiere)>
                                {{ $matiere->nom_matiere }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Statut</label>
                    <select name="valide" class="form-select">
                        <option value="">Tous</option>
                        <option value="0" @selected(request('valide') === '0')>En attente</option>
                        <option value="1" @selected(request('valide') === '1')>Validé</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card theme-card shadow-sm overflow-hidden">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bx bx-book me-2"></i>Liste des émargements</h5>
            <span class="badge theme-header-badge">{{ $emargements->total() }} élément(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3 small fw-bold text-uppercase">Date</th>
                        <th class="py-3 small fw-bold text-uppercase">Enseignant</th>
                        <th class="py-3 small fw-bold text-uppercase">Classe</th>
                        <th class="py-3 small fw-bold text-uppercase">Matière</th>
                        <th class="py-3 small fw-bold text-uppercase">Leçon</th>
                        <th class="py-3 small fw-bold text-uppercase">Heures</th>
                        <th class="py-3 small fw-bold text-uppercase">Statut</th>
                        <th class="px-4 py-3 text-end small fw-bold text-uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($emargements as $emargement)
                        <tr>
                            <td class="px-4 py-3">
                                {{ optional($emargement->date_emargement)->format('d/m/Y H:i') ?: 'N/A' }}
                            </td>
                            <td class="fw-bold">{{ $emargement->enseignant->nom_prenom_enseignant ?? 'N/A' }}</td>
                            <td>{{ $emargement->classe->nom_classe ?? 'N/A' }}</td>
                            <td>{{ $emargement->matiere->nom_matiere ?? 'N/A' }}</td>
                            <td>
                                <span class="fw-semibold">{{ $emargement->lecon->titre ?? 'Leçon '.$emargement->id_lecon }}</span>
                                @if($emargement->chapitre)
                                    <small class="d-block text-muted">{{ $emargement->chapitre }}</small>
                                @endif
                            </td>
                            <td>{{ $emargement->nombre_heure }}</td>
                            <td>
                                @if($emargement->valide)
                                    <span class="badge theme-status theme-status-valid rounded-pill px-3">Validé</span>
                                @else
                                    <span class="badge theme-status theme-status-pending rounded-pill px-3">En attente</span>
                                @endif
                            </td>
                            <td class="px-4 text-end">
                                @if(!$emargement->valide)
                                    @if($emargementPermissions['validate'])
                                        <form action="{{ route('enseignants.emargements.validate', $emargement->id_emargement) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-light btn-sm p-2" title="Valider">
                                                <i class="bx bx-check-circle text-success fs-5"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($emargementPermissions['edit'])
                                        <button class="btn btn-light btn-sm p-2" data-bs-toggle="modal" data-bs-target="#editEmargementModal{{ $emargement->id_emargement }}" title="Modifier">
                                            <i class="bx bx-edit text-warning fs-5"></i>
                                        </button>
                                    @endif
                                    @if($emargementPermissions['delete'])
                                        <form action="{{ route('enseignants.emargements.destroy', $emargement->id_emargement) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cet émargement ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-light btn-sm p-2" title="Supprimer">
                                                <i class="bx bx-trash text-danger fs-5"></i>
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted small">Verrouillé</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">Aucun émargement trouvé.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($emargements->hasPages())
            <div class="card-footer bg-white border-0 p-4">
                {{ $emargements->links() }}
            </div>
        @endif
    </div>

    @if($emargementPermissions['create'])
        @include('enseignants.partials.emargement-form-modal', [
            'modalId' => 'createEmargementModal',
            'title' => 'Nouvel émargement',
            'action' => route('enseignants.emargements.store'),
            'method' => 'POST',
            'emargement' => null,
        ])
    @endif

    @if($emargementPermissions['edit'])
        @foreach($emargements as $emargement)
            @include('enseignants.partials.emargement-form-modal', [
                'modalId' => 'editEmargementModal'.$emargement->id_emargement,
                'title' => 'Modifier un émargement',
                'action' => route('enseignants.emargements.update', $emargement->id_emargement),
                'method' => 'PUT',
                'emargement' => $emargement,
            ])
        @endforeach
    @endif

    <style>
        html[data-theme] .theme-header-badge {
            background-color: var(--accent-light) !important;
            border: 1px solid rgba(255,255,255,0.28);
            color: var(--text-on-accent) !important;
        }
        html[data-theme] .theme-status {
            background-color: var(--accent-light) !important;
            color: var(--theme-primary) !important;
            border: 1px solid var(--border-color);
        }
        html[data-theme] .theme-status-valid::before {
            content: "✓ ";
        }
        html[data-theme] .theme-status-pending::before {
            content: "• ";
        }
    </style>
@endsection
