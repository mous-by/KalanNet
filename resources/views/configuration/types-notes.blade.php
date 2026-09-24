@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('configuration.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">{{ __('configuration.menu_apercu') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('configuration.menu_types_notes') }}</li>
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
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-check me-2"></i>{{ __('configuration.tn_liste_title') }}</h5>
                    <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm"
                            style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                            data-bs-toggle="modal" data-bs-target="#addNewNoteModal">
                        <i class="bi bi-plus-lg"></i>
                        <span>{{ __('configuration.tn_ajouter_type') }}</span>
                    </button>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.types-notes') }}" method="GET" class="col-md-5" data-auto-filter="true">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="{{ __('configuration.tn_search_placeholder') }}" value="{{ request('search') }}">
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('configuration.tn_th_type') }}</th>
                                    <th>{{ __('configuration.tn_th_code') }}</th>
                                    <th>{{ __('configuration.tn_th_note_sur') }}</th>
                                    <th class="text-center" style="width: 150px;">{{ __('configuration.th_actions') }}</th>
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
                                                        title="{{ __('configuration.modifier') }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="{{ route('configuration.types-notes.destroy', $note->id_note) }}" method="POST" onsubmit="return confirm('{{ __('configuration.tn_confirm_delete') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('configuration.supprimer') }}">
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
                                            {{ __('configuration.tn_empty') }}
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
                    <h5 class="modal-title fw-bold" id="addNewNoteModalLabel"><i class="bi bi-plus-circle me-2"></i>{{ __('configuration.tn_modal_create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('configuration.types-notes.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="typeNote" class="form-label fw-bold">{{ __('configuration.tn_th_type') }} <span class="text-danger">*</span></label>
                            <select name="typeNote" id="typeNote" class="form-select" required>
                                <option value="" selected disabled>{{ __('configuration.tn_choisir_type') }}</option>
                                <option value="devoir">{{ __('configuration.tn_type_devoir') }}</option>
                                <option value="composition">{{ __('configuration.tn_type_composition') }}</option>
                                <option value="NT10">{{ __('configuration.tn_type_nt10') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="codeNote" class="form-label fw-bold">{{ __('configuration.tn_th_code') }} <span class="text-danger">*</span></label>
                            <input type="text" id="codeNote" name="codeNote" class="form-control" placeholder="{{ __('configuration.tn_code_placeholder') }}" required>
                            <div class="form-text">{{ __('configuration.tn_code_help') }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="valeur" class="form-label fw-bold">{{ __('configuration.tn_valeur_label') }} <span class="text-danger">*</span></label>
                            <input type="number" step="any" id="valeur" name="valeur" class="form-control" placeholder="Ex: 20" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('configuration.annuler') }}</button>
                        <button type="submit" class="btn text-white fw-bold" style="background-color: var(--theme-accent) !important; border: none;">{{ __('configuration.enregistrer') }}</button>
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
                    <h5 class="modal-title fw-bold" id="editNoteModalLabel"><i class="bi bi-pencil-square me-2"></i>{{ __('configuration.tn_modal_edit_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editNoteForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_typeNote" class="form-label fw-bold">{{ __('configuration.tn_th_type') }} <span class="text-danger">*</span></label>
                            <select name="typeNote" id="edit_typeNote" class="form-select" required>
                                <option value="devoir">{{ __('configuration.tn_type_devoir') }}</option>
                                <option value="composition">{{ __('configuration.tn_type_composition') }}</option>
                                <option value="NT10">{{ __('configuration.tn_type_nt10') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_codeNote" class="form-label fw-bold">{{ __('configuration.tn_th_code') }} <span class="text-danger">*</span></label>
                            <input type="text" id="edit_codeNote" name="codeNote" class="form-control" placeholder="{{ __('configuration.tn_code_placeholder') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_valeur" class="form-label fw-bold">{{ __('configuration.tn_valeur_label') }} <span class="text-danger">*</span></label>
                            <input type="number" step="any" id="edit_valeur" name="valeur" class="form-control" placeholder="Ex: 20" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('configuration.annuler') }}</button>
                        <button type="submit" class="btn text-white fw-bold" style="background-color: var(--theme-accent) !important; border: none;">{{ __('configuration.modifier') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var typeNoteCounts = @json($typeNoteCounts);
            var typeNoteLabels = @json(['devoir' => __('configuration.tn_type_devoir'), 'composition' => __('configuration.tn_code_abrev_composition'), 'NT10' => 'NT10']);

            function suggestedCode(typeNote) {
                if (typeNote === 'NT10') return 'NT10';
                var label = typeNoteLabels[typeNote] || typeNote;
                var count = typeNoteCounts[typeNote] || 0;
                return label + ' ' + (count + 1);
            }

            var addTypeSelect = document.getElementById('typeNote');
            var addCodeInput = document.getElementById('codeNote');
            if (addTypeSelect && addCodeInput) {
                addTypeSelect.addEventListener('change', function () {
                    addCodeInput.value = suggestedCode(addTypeSelect.value);
                });
            }

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
