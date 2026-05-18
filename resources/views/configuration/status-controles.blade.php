@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Statuts de contrôle</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 bg-success alert-dismissible fade show py-2">
            <div class="d-flex align-items-center">
                <div class="font-35 text-white"><i class="bi bi-check-circle-fill"></i></div>
                <div class="ms-3">
                    <div class="text-white">{{ session('success') }}</div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>
        <div class="col-12 col-lg-9">
            <div class="card theme-card shadow-sm mb-5 pb-4">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-shield-exclamation me-2"></i>Liste des statuts de contrôle</h5>
                    <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm" 
                            style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                            data-bs-toggle="modal" data-bs-target="#addNewControleModal">
                        <i class="bi bi-plus-lg"></i>
                        <span>Ajouter un statut</span>
                    </button>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.status-controles') }}" method="GET" class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="Rechercher un statut..." value="{{ request('search') }}">
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Status contrôle</th>
                                    <th class="text-center" style="width: 150px;">Alert ?</th>
                                    <th class="text-center" style="width: 150px;">Pénalité</th>
                                    <th class="text-center" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($statusControles as $controle)
                                    <tr>
                                        <td class="fw-bold text-capitalize">{{ $controle->type_controle }}</td>
                                        <td class="text-center">
                                            @if($controle->alertControle === 'oui')
                                                <span class="badge bg-danger px-3 py-1 d-inline-flex align-items-center gap-1"><i class="bi bi-bell-fill"></i> Oui</span>
                                            @else
                                                <span class="badge bg-secondary px-3 py-1">Non</span>
                                            @endif
                                        </td>
                                        <td class="text-center fw-bold text-danger">{{ $controle->penalite_conduite }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal" data-bs-target="#editControleModal"
                                                        data-id="{{ $controle->id_controle }}"
                                                        data-controle="{{ $controle->type_controle }}"
                                                        data-alert="{{ $controle->alertControle }}"
                                                        data-penalite="{{ $controle->penalite_conduite }}"
                                                        title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="{{ route('configuration.status-controles.destroy', $controle->id_controle) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce statut de contrôle ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            Aucun statut de contrôle trouvé.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($statusControles->hasPages())
                        <div class="mt-4">{{ $statusControles->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout -->
    <div class="modal fade" id="addNewControleModal" tabindex="-1" aria-labelledby="addNewControleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-4" style="border-top-color: var(--theme-accent) !important;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addNewControleModalLabel"><i class="bi bi-plus-circle me-2"></i>Nouveau Statut de Contrôle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('configuration.status-controles.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="controle" class="form-label fw-bold">Status du contrôle <span class="text-danger">*</span></label>
                            <input type="text" id="controle" name="controle" class="form-control" placeholder="Ex: Retard, Absence injustifiée..." required>
                        </div>
                        <div class="mb-3">
                            <label for="penalite_conduite" class="form-label fw-bold">Pénalité de conduite (Points) <span class="text-danger">*</span></label>
                            <input type="number" id="penalite_conduite" name="penalite_conduite" class="form-control" placeholder="Ex: 2" required>
                        </div>
                        <div class="mb-3">
                            <label for="alert" class="form-label fw-bold">Alerte ? <span class="text-danger">*</span></label>
                            <select name="alert" id="alert" class="form-select" required>
                                <option value="oui" selected>Oui (Déclenche une notification)</option>
                                <option value="non">Non</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn text-white fw-bold" style="background-color: var(--theme-accent) !important; border: none;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div class="modal fade" id="editControleModal" tabindex="-1" aria-labelledby="editControleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-4" style="border-top-color: var(--theme-accent) !important;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editControleModalLabel"><i class="bi bi-pencil-square me-2"></i>Modifier le Statut de Contrôle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editControleForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_controle" class="form-label fw-bold">Status du contrôle <span class="text-danger">*</span></label>
                            <input type="text" id="edit_controle" name="controle" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_penalite_conduite" class="form-label fw-bold">Pénalité de conduite (Points) <span class="text-danger">*</span></label>
                            <input type="number" id="edit_penalite_conduite" name="penalite_conduite" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_alert" class="form-label fw-bold">Alerte ? <span class="text-danger">*</span></label>
                            <select name="alert" id="edit_alert" class="form-select" required>
                                <option value="oui">Oui (Déclenche une notification)</option>
                                <option value="non">Non</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn text-white fw-bold" style="background-color: var(--theme-accent) !important; border: none;">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editModal = document.getElementById('editControleModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var controle = button.getAttribute('data-controle');
                var alert = button.getAttribute('data-alert');
                var penalite = button.getAttribute('data-penalite');

                var form = editModal.querySelector('#editControleForm');
                form.action = "{{ url('/configuration/status-controles') }}/" + id;

                editModal.querySelector('#edit_controle').value = controle;
                editModal.querySelector('#edit_penalite_conduite').value = penalite;
                editModal.querySelector('#edit_alert').value = alert;
            });
        });
    </script>
@endsection
