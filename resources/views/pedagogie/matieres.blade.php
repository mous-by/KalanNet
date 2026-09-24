@extends('layouts.app')

@section('content')
    @php
        $canEditMatiere = auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('matieres_modification');
        $canDeleteMatiere = auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('matieres_supprimer');
    @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">{{ __('matieres.title') }}</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ __('matieres.breadcrumb_active') }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('matieres_creation'))
    <div class="mb-3 d-flex justify-content-end">
        <a href="#" class="btn px-4 theme-pill-active" data-bs-toggle="modal" data-bs-target="#addNewCCModal">
            <i class="bi bi-plus-lg me-2"></i>{{ __('matieres.add') }}
        </a>
    </div>
    @endif

    <!-- Main Card -->
    <div class="card theme-card shadow-sm mt-3">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('matieres.th_matiere') }}</th>
                            <th>{{ __('matieres.th_ordres') }}</th>
                            <th class="text-center dt-no-sorting">{{ __('matieres.th_action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matieres as $matiere)
                            <tr>
                                <td class="fw-bold">{{ $matiere->nom_matiere }}</td>
                                <td>{{ $matiere->ordres->pluck('ordre_enseignement')->join(', ') }}</td>
                                <td class="text-center">
                                    @if($canEditMatiere || $canDeleteMatiere)
                                        <div class="dropdown">
                                            <a class="text-muted fs-5" href="#" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                @if($canEditMatiere)
                                                    <li>
                                                        <a class="dropdown-item py-2 edit-matiere" href="#" data-bs-toggle="modal" data-bs-target="#modalCenter" data-id="{{ $matiere->id_matiere }}" data-nom="{{ $matiere->nom_matiere }}" data-ordres='@json($matiere->ordres->pluck('ordre_enseignement')->values())'>
                                                            <i class="bi bi-pencil text-warning me-2"></i>{{ __('matieres.edit') }}
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($canEditMatiere && $canDeleteMatiere)
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif
                                                @if($canDeleteMatiere)
                                                    <li>
                                                        <form action="{{ route('pedagogie.matieres.destroy', $matiere->id_matiere) }}" method="POST" onsubmit="return confirm('{{ __('matieres.confirm_delete') }}');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item py-2 text-danger">
                                                                <i class="bi bi-trash me-2"></i>{{ __('matieres.delete') }}
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
                                <td colspan="3" class="text-center py-4 text-muted">{{ __('matieres.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($matieres->hasPages())
                <div class="mt-4">
                    {{ $matieres->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="addNewCCModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('matieres.create_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('matieres.close') }}"></button>
                </div>
                <form method="POST" action="{{ route('pedagogie.matieres.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('matieres.name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom_matiere" placeholder="{{ __('matieres.name') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('matieres.th_ordres') }} <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($allOrdres as $value => $label)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="ordre_enseignement[]" id="ordre_{{ $loop->index }}" value="{{ $value }}" @disabled(!in_array($value, $ordresAutorises, true))>
                                        <label class="form-check-label" for="ordre_{{ $loop->index }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('matieres.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('matieres.send') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCenter" tabindex="-1" aria-labelledby="modalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">{{ __('matieres.edit_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('matieres.close') }}"></button>
                </div>
                <form method="POST" action="" id="edit-matiere-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('matieres.name_short') }}</label>
                            <input type="text" id="edit_nom_matiere" class="form-control" name="nom_matiere" placeholder="{{ __('matieres.name') }}" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('matieres.th_ordres') }}</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($allOrdres as $value => $label)
                                    <div class="form-check">
                                        <input class="form-check-input edit-ordre" type="checkbox" name="ordre_enseignement[]" id="edit_ordre_{{ $loop->index }}" value="{{ $value }}" @disabled(!in_array($value, $ordresAutorises, true))>
                                        <label class="form-check-label" for="edit_ordre_{{ $loop->index }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('matieres.close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('matieres.edit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('edit-matiere-form');
            const nameInput = document.getElementById('edit_nom_matiere');

            document.querySelectorAll('.edit-matiere').forEach(function (button) {
                button.addEventListener('click', function () {
                    const id = this.dataset.id;
                    const ordres = JSON.parse(this.dataset.ordres || '[]');

                    form.action = '{{ url('/pedagogie/matieres') }}/' + id;
                    nameInput.value = this.dataset.nom || '';

                    document.querySelectorAll('.edit-ordre').forEach(function (checkbox) {
                        checkbox.checked = ordres.includes(checkbox.value);
                    });
                });
            });
        });
    </script>
@endpush
