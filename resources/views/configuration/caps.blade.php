@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">CAP</li>
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
                    <h5 class="mb-0 fw-bold"><i class="bi bi-diagram-3 me-2"></i>Gestion des CAP</h5>
                    <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white" 
                            style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                            data-bs-toggle="modal" data-bs-target="#capCreateModal">
                        <i class="bi bi-plus-lg"></i>
                        <span>Ajouter</span>
                    </button>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.caps') }}" method="GET" class="col-md-5">
                            <input type="text" name="search" class="form-control" placeholder="Nom, code ou localité..." value="{{ request('search') }}">
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>CAP</th>
                                    <th>Code</th>
                                    <th>Localité</th>
                                    <th>Académie</th>
                                    <th>Écoles</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($caps as $cap)
                                    <tr>
                                        <td class="fw-bold">{{ $cap->nom_cap }}</td>
                                        <td><span class="badge theme-icon-soft">{{ $cap->code_cap }}</span></td>
                                        <td>{{ $cap->localite_cap }}</td>
                                        <td>{{ $cap->academie->nom_academie ?? 'N/A' }}</td>
                                        <td>{{ $cap->ecoles_count }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-light btn-sm p-2" data-bs-toggle="modal" data-bs-target="#capEditModal{{ $cap->id_cap }}" title="Modifier">
                                                <i class="bi bi-pencil text-warning"></i>
                                            </button>
                                            <form action="{{ route('configuration.caps.destroy', $cap->id_cap) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ce CAP ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-light btn-sm p-2" title="Supprimer">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4 text-muted">Aucun CAP trouvé.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($caps->hasPages())
                        <div class="mt-4">{{ $caps->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('configuration.partials.cap-modal', [
        'modalId' => 'capCreateModal',
        'title' => 'Nouveau CAP',
        'action' => route('configuration.caps.store'),
        'method' => 'POST',
        'cap' => null,
    ])

    @foreach($caps as $cap)
        @include('configuration.partials.cap-modal', [
            'modalId' => 'capEditModal'.$cap->id_cap,
            'title' => 'Modifier un CAP',
            'action' => route('configuration.caps.update', $cap->id_cap),
            'method' => 'PUT',
            'cap' => $cap,
        ])
    @endforeach
@endsection
