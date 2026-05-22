@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Enseignants</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('enseignants.index') }}">Enseignants</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Cahier de présence</li>
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
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif
    @php($isTeacher = Auth::user()->droit === 'enseignant')

    @if($presencePermissions['create'])
        <div class="mb-3 d-flex justify-content-end">
            <button class="btn px-4 theme-pill-active" data-bs-toggle="modal" data-bs-target="#createPresenceModal">
                <i class="bx bx-plus me-2"></i>Nouvelle présence
            </button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Présences</small>
                    <h4 class="fw-bold mb-0">{{ number_format($presenceSummary['total'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">{{ $presenceSummary['pending'] }} en attente</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Validées</small>
                    <h4 class="fw-bold mb-0">{{ number_format($presenceSummary['validated'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">Cahiers confirmés</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Heures</small>
                    <h4 class="fw-bold mb-0">{{ number_format($presenceSummary['hours'], 2, ',', ' ') }}</h4>
                    <span class="small text-muted">Total déclaré</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase fw-bold">Leçons</small>
                    <h4 class="fw-bold mb-0">{{ number_format($presenceSummary['lessons'], 0, ',', ' ') }}</h4>
                    <span class="small text-muted">Lignes renseignées</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm mb-4">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bx bx-filter-alt me-2"></i>Filtrer les présences</h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('enseignants.presences.filter') }}" method="POST" class="row g-3" data-auto-filter="true">
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
                <div class="col-md-3">
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
                    <label class="form-label small fw-bold text-muted text-uppercase">Statut</label>
                    <select name="valide" class="form-select">
                        <option value="">Tous</option>
                        <option value="0" @selected(request('valide') === '0')>En attente</option>
                        <option value="1" @selected(request('valide') === '1')>Validé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Du</label>
                    <input type="date" name="date_debut" class="form-control" value="{{ request('date_debut') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted text-uppercase">Au</label>
                    <input type="date" name="date_fin" class="form-control" value="{{ request('date_fin') }}">
                </div>
            </form>
        </div>
    </div>

    <div class="card theme-card shadow-sm overflow-hidden">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bx bx-check-square me-2"></i>Liste des présences</h5>
            <span class="badge theme-header-badge">{{ $presences->total() }} élément(s)</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="presence-table-head" style="--bs-table-bg: #fff; --bs-table-color: #212529; background-color: #fff !important;">
                    <tr>
                        <th class="px-4 py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Date</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Enseignant</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Classe</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Leçons</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Heures</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Période</th>
                        <th class="py-3 small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Statut</th>
                        <th class="px-4 py-3 text-end small fw-bold text-uppercase bg-white" style="color: #212529 !important;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($presences as $presence)
                        <tr>
                            <td class="px-4 py-3">{{ optional($presence->date_presence)->format('d/m/Y H:i') ?: 'N/A' }}</td>
                            <td class="fw-bold">{{ $presence->enseignant->nom_prenom_enseignant ?? 'N/A' }}</td>
                            <td>{{ $presence->classe->nom_classe ?? 'N/A' }}</td>
                            <td>
                                @forelse($presence->lecons as $lecon)
                                    <div class="mb-1">
                                        <span class="fw-semibold">{{ $lecon->titre }}</span>
                                        <small class="text-muted d-block">{{ number_format($lecon->progression, 2) }}% - {{ number_format($lecon->nombre_heure, 2) }} h</small>
                                    </div>
                                @empty
                                    <span class="text-muted">Aucune leçon</span>
                                @endforelse
                            </td>
                            <td>{{ number_format($presence->nombre_heure, 2) }}</td>
                            <td>
                                <div>{{ $presence->trimestre->nom_trimestre ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $presence->anneeScolaire->annee ?? '' }}</small>
                            </td>
                            <td>
                                @if($presence->valide)
                                    <span class="badge theme-status theme-status-valid rounded-pill px-3">Validé</span>
                                @else
                                    <span class="badge theme-status theme-status-pending rounded-pill px-3">En attente</span>
                                @endif
                            </td>
                            <td class="px-4 text-end">
                                @if(!$presence->valide)
                                    @if($presencePermissions['validate'])
                                        <form action="{{ route('enseignants.presences.validate', $presence->id_presence) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-light btn-sm p-2" title="Valider">
                                                <i class="bx bx-check-circle text-success fs-5"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($presencePermissions['edit'])
                                        <button class="btn btn-light btn-sm p-2" data-bs-toggle="modal" data-bs-target="#editPresenceModal{{ $presence->id_presence }}" title="Modifier">
                                            <i class="bx bx-edit text-warning fs-5"></i>
                                        </button>
                                    @endif
                                    @if($presencePermissions['delete'])
                                        <form action="{{ route('enseignants.presences.destroy', $presence->id_presence) }}" method="POST" class="d-inline" data-confirm-delete data-confirm-title="Supprimer cette présence ?" data-confirm-text="Les leçons liées à cette présence seront aussi supprimées.">
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
                            <td colspan="8" class="text-center py-5 text-muted">Aucune présence trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($presences->hasPages())
            <div class="card-footer bg-white border-0 p-4">
                {{ $presences->links() }}
            </div>
        @endif
    </div>

    @if($presencePermissions['create'])
        @include('enseignants.partials.presence-form-modal', [
            'modalId' => 'createPresenceModal',
            'title' => 'Nouvelle présence',
            'action' => route('enseignants.presences.store'),
            'method' => 'POST',
            'presence' => null,
        ])
    @endif

    @if($presencePermissions['edit'])
        @foreach($presences as $presence)
            @include('enseignants.partials.presence-form-modal', [
                'modalId' => 'editPresenceModal'.$presence->id_presence,
                'title' => 'Modifier une présence',
                'action' => route('enseignants.presences.update', $presence->id_presence),
                'method' => 'PUT',
                'presence' => $presence,
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
        .presence-table-head th {
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
