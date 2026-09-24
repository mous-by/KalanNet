@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('configuration.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">{{ __('configuration.menu_apercu') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('configuration.menu_filieres') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('configuration.partials.flash')

    <div class="row g-4">
        <div class="col-12 col-lg-3">@include('configuration._menu')</div>
        <div class="col-12 col-lg-9">
            @php
                $canEditFiliere = auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('filieres_modification');
                $canDeleteFiliere = auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('filieres_supprimer');
            @endphp
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3-fill me-2"></i>{{ __('configuration.menu_filieres') }}</h5>
                    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('filieres_creation'))
                        <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white"
                                style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                data-bs-toggle="modal" data-bs-target="#addFiliereModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>{{ __('configuration.ajouter') }}</span>
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 border-start border-info border-4">
                        {{ __('configuration.fil_intro') }}
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('configuration.menu_filieres_singulier') }}</th>
                                    <th class="text-center dt-no-sorting">{{ __('configuration.ut_th_action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($filieres as $filiere)
                                    <tr>
                                        <td class="fw-bold">{{ $filiere->nom_filiere }}</td>
                                        <td class="text-center">
                                            @if($canEditFiliere || $canDeleteFiliere)
                                                <div class="dropdown">
                                                    <a class="text-muted fs-5" href="#" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots"></i>
                                                    </a>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                        @if($canEditFiliere)
                                                            <li>
                                                                <a class="dropdown-item py-2 edit-filiere" href="#" data-bs-toggle="modal" data-bs-target="#editFiliereModal" data-id="{{ $filiere->id_filiere }}" data-nom="{{ $filiere->nom_filiere }}">
                                                                    <i class="bi bi-pencil text-warning me-2"></i>{{ __('configuration.modifier') }}
                                                                </a>
                                                            </li>
                                                        @endif
                                                        @if($canEditFiliere && $canDeleteFiliere)
                                                            <li><hr class="dropdown-divider"></li>
                                                        @endif
                                                        @if($canDeleteFiliere)
                                                            <li>
                                                                <form action="{{ route('configuration.filieres.destroy', $filiere->id_filiere) }}" method="POST" onsubmit="return confirm('{{ __('configuration.fil_confirm_delete') }}');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item py-2 text-danger">
                                                                        <i class="bi bi-trash me-2"></i>{{ __('configuration.supprimer') }}
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center py-4 text-muted">{{ __('configuration.fil_empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($filieres->hasPages())
                        <div class="mt-4">
                            {{ $filieres->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addFiliereModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('configuration.fil_modal_create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('configuration.fermer') }}"></button>
                </div>
                <form method="POST" action="{{ route('configuration.filieres.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('configuration.fil_nom_label') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom_filiere" placeholder="{{ __('configuration.fil_nom_placeholder') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('configuration.annuler') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('configuration.fil_envoyer') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editFiliereModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('configuration.fil_modal_edit_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('configuration.fermer') }}"></button>
                </div>
                <form method="POST" action="" id="edit-filiere-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('configuration.fil_nom_label') }}</label>
                            <input type="text" id="edit_nom_filiere" class="form-control" name="nom_filiere" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('configuration.fermer') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('configuration.modifier') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('edit-filiere-form');
            const nameInput = document.getElementById('edit_nom_filiere');

            document.querySelectorAll('.edit-filiere').forEach(function (button) {
                button.addEventListener('click', function () {
                    form.action = '{{ url('/configuration/filieres') }}/' + this.dataset.id;
                    nameInput.value = this.dataset.nom || '';
                });
            });
        });
    </script>
@endpush
