@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Académies</li>
                </ol>
            </nav>
        </div>
    </div>

    @include('configuration.partials.flash')

    <div class="row g-4">
        <div class="col-12 col-lg-3">@include('configuration._menu')</div>
        <div class="col-12 col-lg-9">
            <div class="card theme-card shadow-sm">
                <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bank me-2"></i>Gestion des académies</h5>
                    @if(auth()->user()->droit === 'SupAdmin')
                        <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white" 
                                style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                data-bs-toggle="modal" data-bs-target="#academieCreateModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>Ajouter</span>
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.academies') }}" method="GET" class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Nom, code ou localité..." value="{{ request('search') }}">
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Académie</th>
                                    <th>Code</th>
                                    <th>Localité</th>
                                    <th>CAP</th>
                                    <th>Écoles</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($academies as $academie)
                                    <tr>
                                        <td class="fw-bold">{{ $academie->nom_academie }}</td>
                                        <td><span class="badge theme-icon-soft">{{ $academie->code_academie }}</span></td>
                                        <td>{{ $academie->localite_academie }}</td>
                                        <td>{{ $academie->caps_count }}</td>
                                        <td>{{ $academie->ecoles_count }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-light btn-sm p-2" data-bs-toggle="modal" data-bs-target="#academieEditModal{{ $academie->id_academie }}" title="Modifier">
                                                <i class="bi bi-pencil text-warning"></i>
                                            </button>
                                            <form action="{{ route('configuration.academies.destroy', $academie->id_academie) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette académie ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-light btn-sm p-2" title="Supprimer">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Aucune académie trouvée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($academies->hasPages())
                        <div class="mt-4">{{ $academies->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('configuration.partials.academie-modal', [
        'modalId' => 'academieCreateModal',
        'title' => 'Nouvelle académie',
        'action' => route('configuration.academies.store'),
        'method' => 'POST',
        'academie' => null,
    ])

    @foreach($academies as $academie)
        @include('configuration.partials.academie-modal', [
            'modalId' => 'academieEditModal'.$academie->id_academie,
            'title' => 'Modifier une académie',
            'action' => route('configuration.academies.update', $academie->id_academie),
            'method' => 'PUT',
            'academie' => $academie,
        ])
    @endforeach
@endsection
