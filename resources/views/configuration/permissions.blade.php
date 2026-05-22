@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Permissions</li>
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
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock me-2"></i>Référentiel des permissions</h5>
                    @if(Auth::user()->droit === 'SupAdmin')
                        <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white" 
                                style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                data-bs-toggle="modal" data-bs-target="#addNewPermissionModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>Ajouter</span>
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.permissions') }}" method="GET" class="col-md-5" data-auto-filter="true">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher une permission..." value="{{ request('search') }}">
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Permission</th>
                                    <th>Utilisateurs liés</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permissions as $permission)
                                    <tr>
                                        <td>{{ $permission->id }}</td>
                                        <td class="fw-bold">{{ $permission->name }}</td>
                                        <td><span class="badge bg-light text-primary">{{ $permission->users_count }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Aucune permission trouvée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($permissions->hasPages())
                        <div class="mt-4">{{ $permissions->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Permission -->
    <div class="modal fade" id="addNewPermissionModal" tabindex="-1" aria-labelledby="addNewPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--theme-accent) !important;">
                    <h5 class="modal-title fw-bold" id="addNewPermissionModalLabel">
                        <i class="bi bi-shield-plus me-2"></i>Nouvelle Permission
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('configuration.permissions.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold">Nom de la permission</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Ex: types_notes_apercu">
                            <small class="text-muted">Recommandé : minuscules séparées par des tirets bas (_).</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn text-white px-4 fw-semibold" style="background-color: var(--theme-accent) !important;">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
