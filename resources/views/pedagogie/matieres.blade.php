@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Matières</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Listes des matières</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(auth()->user()->droit === 'SupAdmin' || auth()->user()->userHasPermission('matieres_création'))
    <div class="mb-3 d-flex justify-content-end">
        <a href="#" class="btn px-4 theme-pill-active" data-bs-toggle="modal" data-bs-target="#addNewCCModal">
            <i class="bi bi-plus-lg me-2"></i>Matière
        </a>
    </div>
    @endif

    <!-- Main Card (Disposition Alliance-Team) -->
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
                            <th>Matière</th>
                            <th>Ordre(s) d'enseignement</th>
                            <th class="text-center dt-no-sorting">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matieres as $matiere)
                            <tr>
                                <td class="fw-bold">{{ $matiere->nom_matiere }}</td>
                                <td>{{ $matiere->ordres->pluck('ordre_enseignement')->join(', ') }}</td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a class="text-muted fs-5" href="#" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2 edit-matiere" href="#" data-bs-toggle="modal" data-bs-target="#modalCenter" data-id="{{ $matiere->id_matiere }}" data-nom="{{ $matiere->nom_matiere }}" data-ordres='@json($matiere->ordres->pluck('ordre_enseignement')->values())'>
                                                    <i class="bi bi-pencil text-warning me-2"></i>Modifier
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('pedagogie.matieres.destroy', $matiere->id_matiere) }}" method="POST" onsubmit="return confirm('Supprimer cette matière ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item py-2 text-danger">
                                                        <i class="bi bi-trash me-2"></i>Supprimer
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">Aucune matière n'a encore été créée.</td>
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
                    <h5 class="modal-title">Enregistrement de matière</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="{{ route('pedagogie.matieres.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom de la matière <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom_matiere" placeholder="Nom de la matière" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Ordre(s) d'enseignement <span class="text-danger">*</span></label>
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
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCenter" tabindex="-1" aria-labelledby="modalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card theme-card">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCenterTitle">Modifier matière</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form method="POST" action="" id="edit-matiere-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nom matière</label>
                            <input type="text" id="edit_nom_matiere" class="form-control" name="nom_matiere" placeholder="Nom de la matière" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Ordre(s) d'enseignement</label>
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
