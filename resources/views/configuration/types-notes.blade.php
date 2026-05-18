@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Types de notes</li>
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
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-check me-2"></i>Liste des types de notes</h5>
                    <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm" 
                            style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                            data-bs-toggle="modal" data-bs-target="#addNewNoteModal">
                        <i class="bi bi-plus-lg"></i>
                        <span>Ajouter un type</span>
                    </button>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.types-notes') }}" method="GET" class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="Rechercher un type ou code..." value="{{ request('search') }}">
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Type Notes</th>
                                    <th>Code</th>
                                    <th>Note sur...</th>
                                    <th class="text-center" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($typesNotes as $note)
                                    <tr>
                                        <td class="fw-bold text-capitalize">{{ $note->typeNote }}</td>
                                        <td><span class="badge bg-secondary px-2 py-1">{{ $note->codeNote }}</span></td>
                                        <td class="fw-bold">{{ $note->valeur }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal" data-bs-target="#editNoteModal"
                                                        data-id="{{ $note->id_note }}"
                                                        data-type="{{ $note->typeNote }}"
                                                        data-code="{{ $note->codeNote }}"
                                                        data-valeur="{{ $note->valeur }}"
                                                        title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="{{ route('configuration.types-notes.destroy', $note->id_note) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce type de note ?')">
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
                                            Aucun type de note trouvé.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($typesNotes->hasPages())
                        <div class="mt-4">{{ $typesNotes->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout -->
    <div class="modal fade" id="addNewNoteModal" tabindex="-1" aria-labelledby="addNewNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-4" style="border-top-color: var(--theme-accent) !important;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addNewNoteModalLabel"><i class="bi bi-plus-circle me-2"></i>Nouveau Type de Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('configuration.types-notes.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="typeNote" class="form-label fw-bold">Type Notes <span class="text-danger">*</span></label>
                            <select name="typeNote" id="typeNote" class="form-select" required>
                                <option value="" selected disabled>Choisir un type</option>
                                <option value="devoir">Devoir</option>
                                <option value="composition">Composition</option>
                                <option value="NT10">Notes sur 10</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="codeNote" class="form-label fw-bold">Code <span class="text-danger">*</span></label>
                            <input type="text" id="codeNote" name="codeNote" class="form-control" placeholder="Ex: Devoir 1, Comp 1..." required>
                        </div>
                        <div class="mb-3">
                            <label for="valeur" class="form-label fw-bold">Note sur... (Ex: 10, 20) <span class="text-danger">*</span></label>
                            <input type="number" step="any" id="valeur" name="valeur" class="form-control" placeholder="Ex: 20" required>
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
    <div class="modal fade" id="editNoteModal" tabindex="-1" aria-labelledby="editNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-4" style="border-top-color: var(--theme-accent) !important;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editNoteModalLabel"><i class="bi bi-pencil-square me-2"></i>Modifier le Type de Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editNoteForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_typeNote" class="form-label fw-bold">Type Notes <span class="text-danger">*</span></label>
                            <select name="typeNote" id="edit_typeNote" class="form-select" required>
                                <option value="devoir">Devoir</option>
                                <option value="composition">Composition</option>
                                <option value="NT10">Notes sur 10</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_codeNote" class="form-label fw-bold">Code <span class="text-danger">*</span></label>
                            <input type="text" id="edit_codeNote" name="codeNote" class="form-control" placeholder="Ex: Devoir 1, Comp 1..." required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_valeur" class="form-label fw-bold">Note sur... (Ex: 10, 20) <span class="text-danger">*</span></label>
                            <input type="number" step="any" id="edit_valeur" name="valeur" class="form-control" placeholder="Ex: 20" required>
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
            var editModal = document.getElementById('editNoteModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var id = button.getAttribute('data-id');
                var type = button.getAttribute('data-type');
                var code = button.getAttribute('data-code');
                var valeur = button.getAttribute('data-valeur');

                var form = editModal.querySelector('#editNoteForm');
                form.action = "{{ url('/configuration/types-notes') }}/" + id;

                editModal.querySelector('#edit_typeNote').value = type;
                editModal.querySelector('#edit_codeNote').value = code;
                editModal.querySelector('#edit_valeur').value = valeur;
            });
        });
    </script>
@endsection
