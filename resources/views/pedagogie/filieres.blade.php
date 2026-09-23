@extends('layouts.app')

@section('content')
    @php
        $canEditFiliere = auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('filieres_modification');
        $canDeleteFiliere = auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('filieres_supprimer');
    @endphp
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Filières</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste des filières</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('filieres_creation'))
    <div class="mb-3 d-flex justify-content-end">
        <a href="#" class="btn px-4 theme-pill-active" data-bs-toggle="modal" data-bs-target="#addFiliereModal">
            <i class="bi bi-plus-lg me-2"></i>Filière
        </a>
    </div>
    @endif

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
                            <th>Filière</th>
                            <th class="text-center dt-no-sorting">Action</th>
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
                                                            <i class="bi bi-pencil text-warning me-2"></i>Modifier
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($canEditFiliere && $canDeleteFiliere)
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif
                                                @if($canDeleteFiliere)
                                                    <li>
                                                        <form action="{{ route('pedagogie.filieres.destroy', $filiere->id_filiere) }}" method="POST" onsubmit="return confirm('Supprimer cette filière ?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="dropdown-item py-2 text-danger">
                                                                <i class="bi bi-trash me-2"></i>Supprimer
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
                                <td colspan="2" class="text-center py-4 text-muted">Aucune filière n'a encore été créée.</td>
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

    <div class="modal fade" id="addFiliereModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title">Enregistrement de filière</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('pedagogie.filieres.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de la filière <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom_filiere" placeholder="Ex : Infirmier, Sage-femme..." required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editFiliereModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier filière</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="" id="edit-filiere-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de la filière</label>
                            <input type="text" id="edit_nom_filiere" class="form-control" name="nom_filiere" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
                        <button type="submit" class="btn btn-primary">Modifier</button>
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
                    form.action = '{{ url('/pedagogie/filieres') }}/' + this.dataset.id;
                    nameInput.value = this.dataset.nom || '';
                });
            });
        });
    </script>
@endpush
