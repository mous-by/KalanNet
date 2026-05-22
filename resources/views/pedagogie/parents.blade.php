@extends('layouts.app')

@section('content')
    @php
        $user = Auth::user();
        $canCreate = $user->droit === 'SupAdmin' || $user->userHasPermission('parents_creation');
        $canEdit = $user->droit === 'SupAdmin' || $user->userHasPermission('parents_modification');
        $canDelete = $user->droit === 'SupAdmin' || $user->userHasPermission('parents_supprimer');
    @endphp

    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Élèves & Parents</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Parents d'élèves</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
    @endif

    <div class="row row-cols-1 row-cols-md-3 g-3 mb-3">
        <div class="col">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <div>
                        <p class="mb-1 text-muted small">Parents enregistrés</p>
                        <h4 class="mb-0 fw-bold">{{ number_format($stats['parents'], 0, ',', ' ') }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-bell-fill fs-5"></i>
                    </div>
                    <div>
                        <p class="mb-1 text-muted small">Contacts à informer</p>
                        <h4 class="mb-0 fw-bold">{{ number_format($stats['contacts_informables'], 0, ',', ' ') }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-person-check-fill fs-5"></i>
                    </div>
                    <div>
                        <p class="mb-1 text-muted small">Élèves avec contact</p>
                        <h4 class="mb-0 fw-bold">{{ number_format($stats['eleves_rattaches'], 0, ',', ' ') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card theme-card shadow-sm mb-3">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0 fw-bold">Recherche automatique</h5>
            @if($canCreate)
                <a href="{{ route('pedagogie.parents.create') }}"
                   class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white"
                   style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;">
                    <i class="bi bi-plus-lg"></i>
                    <span>Ajouter Parent</span>
                </a>
            @else
                <span class="badge bg-light text-primary ms-auto">Filtrage instantané</span>
            @endif
        </div>
        <div class="card-body p-4">
            <form action="{{ route('pedagogie.parents') }}" method="GET" class="row g-3 align-items-end" data-auto-filter="true">
                <div class="col-md-6">
                    <label class="form-label" for="search_parent">Nom, téléphone, email ou élève</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" id="search_parent" class="form-control" placeholder="Ex: Aminata, 76000000, matricule..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="id_classe">Classe de l'élève</label>
                    <select class="form-select" id="id_classe" name="id_classe">
                        <option value="">Toutes les classes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id_classe }}" @selected(request('id_classe') == $classe->id_classe)>
                                {{ $classe->nom_classe }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <a href="{{ route('pedagogie.parents') }}" class="btn btn-light w-100" title="Effacer les filtres">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0 fw-bold">Liste des parents</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Parent</th>
                        <th>Contact</th>
                        <th>Élèves rattachés</th>
                        <th>Informer</th>
                        @if($canEdit || $canDelete)
                            <th class="text-center" style="width: 120px;">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($parents as $parent)
                        @php
                            $informables = $parent->eleves->filter(fn ($eleve) => $eleve->pivot->informer === 'Oui')->count();
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">{{ $parent->nom_prenom_parent }}</h6>
                                        <span class="text-muted small">{{ $parent->genre ?: 'Genre non renseigné' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone me-1"></i>{{ $parent->telephone_parent }}</div>
                                @if($parent->email_parent)
                                    <div class="text-muted small"><i class="bi bi-envelope me-1"></i>{{ $parent->email_parent }}</div>
                                @else
                                    <div class="text-muted small">Email non renseigné</div>
                                @endif
                            </td>
                            <td>
                                @forelse($parent->eleves as $eleve)
                                    <div class="mb-1">
                                        <span class="fw-bold">{{ $eleve->prenom_eleve }} {{ $eleve->nom_eleve }}</span>
                                        <span class="text-muted small">({{ $eleve->classe->nom_classe ?? 'Classe non définie' }})</span>
                                        <span class="badge bg-light text-dark">{{ $eleve->pivot->lien_parent }}</span>
                                    </div>
                                @empty
                                    <span class="text-muted">Aucun élève rattaché</span>
                                @endforelse
                            </td>
                            <td>
                                @if($informables > 0)
                                    <span class="badge bg-success">{{ $informables }} contact(s)</span>
                                @else
                                    <span class="badge bg-secondary">Non</span>
                                @endif
                            </td>
                            @if($canEdit || $canDelete)
                                <td class="text-center">
                                    <div class="btn-group">
                                        @if($canEdit)
                                            <a href="{{ route('pedagogie.parents.edit', $parent->id_parent) }}" class="btn btn-light btn-sm p-2 me-1" title="Modifier">
                                                <i class="bi bi-pencil text-warning"></i>
                                            </a>
                                        @endif
                                        @if($canDelete)
                                            <form action="{{ route('pedagogie.parents.destroy', $parent->id_parent) }}" method="POST" data-confirm-delete data-confirm-title="Supprimer ce parent ?" data-confirm-text="Ses rattachements aux élèves seront retirés. Cette action est définitive.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-light btn-sm p-2" title="Supprimer">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit || $canDelete ? 5 : 4 }}" class="text-center py-5">
                                <i class="bi bi-people fs-1 d-block mb-3 text-muted"></i>
                                <h6 class="fw-bold">Aucun parent trouvé</h6>
                                <p class="text-muted mb-0">Essayez un autre filtre ou ajoutez un nouveau parent.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($parents->hasPages())
            <div class="card-footer bg-white border-0 p-4">
                {{ $parents->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-confirm-delete]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    if (!window.Swal) {
                        if (confirm(form.dataset.confirmText || 'Confirmer la suppression ?')) {
                            form.submit();
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
                        cancelButtonText: 'Annuler'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
