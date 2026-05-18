@extends('layouts.app')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between bg-card p-4 rounded-4 shadow-sm">
                <div>
                    <h2 class="mb-1 fw-bold">Notes & Évaluations</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Accueil</a></li>
                            <li class="breadcrumb-item active">Évaluations</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>Saisir des Notes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('evaluations.index') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Classe</label>
                    <select name="id_classe" class="form-select rounded-3">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" {{ request('id_classe') == $classe->id_classe ? 'selected' : '' }}>
                                {{ $classe->nom_classe }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted text-uppercase">Matière</label>
                    <select name="id_matiere" class="form-select rounded-3">
                        <option value="">Toutes les matières</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id_matiere }}" {{ request('id_matiere') == $matiere->id_matiere ? 'selected' : '' }}>
                                {{ $matiere->nom_matiere }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 rounded-3">Filtrer les notes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notes Table -->
    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3 border-0 small fw-bold text-muted text-uppercase">Élève</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Matière</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Classe</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Note</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Période</th>
                        <th class="px-4 py-3 border-0 text-end small fw-bold text-muted text-uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evaluations as $note)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="fw-bold">{{ $note->eleve->prenom_eleve }} {{ $note->eleve->nom_eleve }}</div>
                                <small class="text-muted">{{ $note->eleve->matricule }}</small>
                            </td>
                            <td>{{ $note->matiere->nom_matiere }}</td>
                            <td>{{ $note->classe->nom_classe }}</td>
                            <td>
                                <span class="badge {{ $note->note >= 10 ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger' }} fs-6 px-3 rounded-pill">
                                    {{ number_format($note->note, 2) }} / 20
                                </span>
                            </td>
                            <td>{{ $note->mois ?: 'N/A' }}</td>
                            <td class="px-4 text-end">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-circle p-2" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-3">
                                        <li><a class="dropdown-item py-2" href="#"><i class="bi bi-pencil me-2 text-warning"></i> Modifier</a></li>
                                        <li><a class="dropdown-item py-2" href="{{ route('pedagogie.bulletins.download', $note->id_eleve) }}"><i class="bi bi-file-earmark-pdf me-2 text-primary"></i> Voir Bulletin</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item py-2 text-danger" href="#"><i class="bi bi-trash me-2"></i> Supprimer</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
                                    Aucune note enregistrée pour ces critères.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($evaluations->hasPages())
            <div class="card-footer bg-white border-0 p-4">
                {{ $evaluations->links() }}
            </div>
        @endif
    </div>

    <style>
        .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
        .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    </style>
@endsection
