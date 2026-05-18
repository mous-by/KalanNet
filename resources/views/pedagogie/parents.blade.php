@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Parents</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Parents d'Élèves</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="mb-3 d-flex justify-content-end">
        <a href="{{ route('pedagogie.parents.create') }}" class="btn px-4 theme-pill-active">
            <i class="bi bi-plus-lg me-2"></i>Nouveau Parent
        </a>
    </div>


    <!-- Search -->
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('pedagogie.parents.filter') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="Rechercher par nom ou téléphone..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-3">Rechercher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Parents Table -->
    <div class="card theme-card shadow-sm overflow-hidden">
        @if(session('success'))
            <div class="alert alert-success border-0 border-start border-success border-4 m-3 mb-0">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger border-0 border-start border-danger border-4 m-3 mb-0">{{ session('error') }}</div>
        @endif
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3 border-0 small fw-bold text-muted text-uppercase">Parent</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Contact</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Genre</th>
                        <th class="py-3 border-0 small fw-bold text-muted text-uppercase">Élèves Rattachés</th>
                        <th class="px-4 py-3 border-0 text-end small fw-bold text-muted text-uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parents as $parent)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary-soft text-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-fill"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $parent->nom_prenom_parent }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="bi bi-telephone me-1"></i> {{ $parent->telephone_parent }}</div>
                                    @if($parent->email_parent)
                                        <div class="text-muted"><i class="bi bi-envelope me-1"></i> {{ $parent->email_parent }}</div>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $parent->genre }}</td>
                            <td>
                                <span class="badge bg-light text-primary rounded-pill">
                                    {{ $parent->eleves->count() }} élève(s)
                                </span>
                            </td>
                            <td class="px-4 text-end">
                                <div class="btn-group">
                                    <a href="{{ route('pedagogie.parents.edit', $parent->id_parent) }}" class="btn btn-light btn-sm p-2 me-1" title="Modifier"><i class="bi bi-pencil text-warning"></i></a>
                                    <form action="{{ route('pedagogie.parents.destroy', $parent->id_parent) }}" method="POST" onsubmit="return confirm('Supprimer ce parent ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm p-2" title="Supprimer"><i class="bi bi-trash text-danger"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-3"></i>
                                    Aucun parent trouvé.
                                </div>
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

@endsection
