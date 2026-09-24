@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('configuration.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    @if(auth()->user()->droit === 'SupAdmin')
                        <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">{{ __('configuration.menu_apercu') }}</a></li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">{{ __('configuration.menu_classes_officielles') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('configuration.partials.flash')

    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('configuration._menu')
        </div>
        <div class="col-12 col-lg-9">
            <div class="card theme-card shadow-sm mb-5 pb-4">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-building-check me-2"></i>{{ __('configuration.menu_classes_officielles') }}</h5>
                    <div class="d-flex gap-2">
                        @if(auth()->user()->droit === 'SupAdmin')
                            <a href="{{ route('classes.associations') }}" class="btn btn-sm btn-outline-light shadow-sm">
                                <i class="bi bi-link-45deg me-1"></i>{{ __('configuration.classe_off_associer') }}
                            </a>
                        @endif
                        @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('classes_officielles_apercu'))
                            <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm"
                                    style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                    data-bs-toggle="modal" data-bs-target="#addClasseOfficielleModal">
                                <i class="bi bi-plus-lg"></i>
                                <span>{{ __('configuration.menu_classes_officielles_singulier') }}</span>
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.classes-officielles') }}" method="GET" class="col-md-5" data-auto-filter="true">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0"
                                       placeholder="{{ __('configuration.classe_off_search_placeholder') }}" value="{{ request('search') }}">
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('configuration.th_numero') }}</th>
                                    <th>{{ __('configuration.menu_classes_officielles_singulier') }}</th>
                                    <th>{{ __('configuration.classe_off_ordre_label') }}</th>
                                    <th>{{ __('configuration.classe_off_classes_associees') }}</th>
                                    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('classes_officielles_apercu'))
                                        <th class="text-center" style="width:120px;">{{ __('configuration.th_actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($classesOfficielles as $index => $classeOfficielle)
                                    <tr>
                                        <td>{{ $classesOfficielles->firstItem() + $index }}</td>
                                        <td class="fw-bold">{{ $classeOfficielle->nom_classe_officielle }}</td>
                                        <td>{{ $ordres[$classeOfficielle->ordre_enseignement] ?? $classeOfficielle->ordre_enseignement }}</td>
                                        <td>
                                            <span class="badge bg-light text-primary border border-primary-subtle rounded-pill">
                                                {{ __('configuration.classe_off_count', ['count' => $classeOfficielle->classes_count]) }}
                                            </span>
                                        </td>
                                        @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('classes_officielles_apercu'))
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                                            data-bs-toggle="modal" data-bs-target="#editClasseOfficielleModal"
                                                            data-id="{{ $classeOfficielle->id_classe_officielle }}"
                                                            data-nom="{{ $classeOfficielle->nom_classe_officielle }}"
                                                            data-ordre="{{ $classeOfficielle->ordre_enseignement }}"
                                                            title="{{ __('configuration.modifier') }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    @if($classeOfficielle->classes_count === 0)
                                                        <form action="{{ route('configuration.classes-officielles.destroy', $classeOfficielle->id_classe_officielle) }}" method="POST"
                                                              onsubmit="return confirm('{{ __('configuration.classe_off_confirm_delete') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('configuration.supprimer') }}">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            {{ __('configuration.classe_off_empty') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($classesOfficielles->hasPages())
                        <div class="mt-4">{{ $classesOfficielles->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Ajout --}}
    <div class="modal fade" id="addClasseOfficielleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-4" style="border-top-color: var(--theme-accent) !important;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>{{ __('configuration.classe_off_modal_create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('configuration.classes-officielles.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @include('configuration.partials.classe-officielle-fields', ['prefix' => 'add_'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('configuration.annuler') }}</button>
                        <button type="submit" class="btn text-white fw-bold"
                                style="background-color: var(--theme-accent) !important; border: none;">{{ __('configuration.enregistrer') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Modification --}}
    <div class="modal fade" id="editClasseOfficielleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-top border-4" style="border-top-color: var(--theme-accent) !important;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>{{ __('configuration.classe_off_modal_edit_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editClasseOfficielleForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        @include('configuration.partials.classe-officielle-fields', ['prefix' => 'edit_'])
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('configuration.annuler') }}</button>
                        <button type="submit" class="btn text-white fw-bold"
                                style="background-color: var(--theme-accent) !important; border: none;">{{ __('configuration.modifier') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var editModal = document.getElementById('editClasseOfficielleModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                var btn = event.relatedTarget;
                var id    = btn.getAttribute('data-id');
                var nom   = btn.getAttribute('data-nom');
                var ordre = btn.getAttribute('data-ordre');

                var form = editModal.querySelector('#editClasseOfficielleForm');
                form.action = "{{ url('/configuration/classes-officielles') }}/" + id;

                editModal.querySelector('#edit_nom_classe_officielle').value = nom;
                editModal.querySelector('#edit_ordre_enseignement').value    = ordre;
            });
        });
    </script>
@endsection
