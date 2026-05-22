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
    @php($isTeacher = Auth::user()->droit === 'enseignant')

    @if($emargementPermissions['create'])
        <div class="mb-3 d-flex justify-content-end">
            <button class="btn px-4 theme-pill-active" data-bs-toggle="modal" data-bs-target="#createEmargementModal" @disabled(empty($emargementFormData['hasAssignments']))>
                <i class="bx bx-plus me-2"></i>Nouvel émargement
            </button>
        </div>
        @if(empty($emargementFormData['hasAssignments']))
            <div class="alert alert-warning border-0 border-start border-warning border-4">
                Aucune affectation classe/matière n’est encore définie pour permettre l’émargement.
            </div>
        @elseif(empty($emargementFormData['hasTimetableCourses']))
            <div class="alert alert-info border-0 border-start border-info border-4">
                Aucun emploi du temps n’est encore défini. Vous pouvez tout de même émarger à partir des affectations; l’EDT servira d’aide dès qu’il sera programmé.
            </div>
        @endif
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Émargements</small>
                    <h4 class="fw-bold mb-0">{{ number_format($emargementSummary['total'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">{{ $emargementSummary['pending'] }} en attente</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Heures validées</small>
                    <h4 class="fw-bold mb-0">{{ number_format($emargementSummary['validated_hours'], 2, ',', ' ') }}</h4>
                    <span class="small text-muted">Base pédagogique validée</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Leçons exécutées</small>
                    <h4 class="fw-bold mb-0">{{ number_format($emargementSummary['lessons'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">Leçons distinctes</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Paiement VCT estimé</small>
                    <h4 class="fw-bold mb-0">{{ number_format($emargementSummary['vct_amount'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">FCFA sur heures validées</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bx bx-filter-alt me-2"></i>Filtrer les émargements</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('enseignants.emargements.filter') }}" method="POST" class="row g-3" data-auto-filter="true">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted text-uppercase">Enseignant</label>
                    <select name="id_enseignant" class="form-select" @disabled($isTeacher)>
                        <option value="">Tous</option>
                        @foreach($enseignants as $enseignant)
                            <option value="{{ $enseignant->id_enseignant }}" @selected(request('id_enseignant') == $enseignant->id_enseignant)>
                                {{ $enseignant->nom_prenom_enseignant }}
                            </option>
                        @endforeach
                    </select>
                    @if($isTeacher)
                        <input type="hidden" name="id_enseignant" value="{{ Auth::user()->id_enseignant }}">
                    @endif
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
                <thead class="emargement-table-head" style="--bs-table-bg: #fff; --bs-table-color: #212529; background-color: #fff !important;">
                    <tr>
                        <th class="px-4 py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Date</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Enseignant</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Classe</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Matière</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Leçon</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Heures</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Statut</th>
                        <th class="px-4 py-3 text-end small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Actions</th>
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
                                        <form action="{{ route('enseignants.emargements.destroy', $emargement->id_emargement) }}" method="POST" class="d-inline" data-confirm-delete data-confirm-title="Supprimer cet émargement ?" data-confirm-text="Cette action est définitive. L’émargement ne sera plus visible dans la liste.">
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
        .emargement-table-head th {
            background: #fff !important;
            background-color: #fff !important;
            --bs-table-bg: #fff !important;
            box-shadow: none !important;
            color: #212529 !important;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();

                        if (!window.Swal) {
                            if (confirm(form.dataset.confirmTitle || 'Confirmer la suppression ?')) {
                                HTMLFormElement.prototype.submit.call(form);
                            }

                            return;
                        }

                        Swal.fire({
                            title: form.dataset.confirmTitle || 'Confirmer la suppression ?',
                            text: form.dataset.confirmText || 'Cette action est définitive.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Oui, supprimer',
                            cancelButtonText: 'Annuler',
                            reverseButtons: true,
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                HTMLFormElement.prototype.submit.call(form);
                            }
                        });
                    });
                });
            });
        </script>
    @endpush
@endsection
