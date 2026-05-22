@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Configuration</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bx bx-home"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('configuration.index') }}">Aperçu</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Écoles</li>
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
                    <h5 class="mb-0 fw-bold"><i class="bx bx-building me-2"></i>Gestion des écoles</h5>
                    @if(Auth::user()->droit === 'SupAdmin')
                        <button type="button" class="btn btn-sm d-flex align-items-center gap-1 shadow-sm text-white" 
                                style="background-color: var(--theme-accent) !important; color: var(--text-on-accent) !important; border: none;"
                                data-bs-toggle="modal" data-bs-target="#ecoleCreateModal">
                            <i class="bi bi-plus-lg"></i>
                            <span>Ajouter</span>
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end align-items-center flex-wrap mb-3 gap-3">
                        <form action="{{ route('configuration.ecoles') }}" method="GET" class="col-md-5" data-auto-filter="true">
                            <input type="text" name="search" class="form-control" placeholder="Rechercher une école..." value="{{ request('search') }}">
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>École</th>
                                    <th>Type</th>
                                    <th>Académie</th>
                                    <th>CAP</th>
                                    <th>Contact</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ecoles as $ecole)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded border d-flex align-items-center justify-content-center overflow-hidden" style="width: 42px; height: 42px; background: #fff !important;">
                                                    @if($ecole->logoEcole)
                                                        <img src="{{ asset($ecole->logoEcole) }}" alt="" class="w-100 h-100 object-fit-contain p-1">
                                                    @else
                                                        <i class="bx bx-building text-muted"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $ecole->nomEcole }}</div>
                                                    <small class="text-muted">{{ $ecole->adresse ?? 'Adresse non renseignée' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $ecole->typeEcole }}</td>
                                        <td>{{ $ecole->academieRef->nom_academie ?? $ecole->academie ?? 'N/A' }}</td>
                                        <td>{{ $ecole->capRef->nom_cap ?? $ecole->cap ?? 'N/A' }}</td>
                                        <td>
                                            <div>{{ $ecole->telephone ?? 'N/A' }}</div>
                                            <small class="text-muted">{{ $ecole->email ?: 'Email non renseigné' }}</small>
                                        </td>
                                        <td><span class="badge theme-icon-soft">{{ ucfirst($ecole->statut ?? 'public') }}</span></td>
                                        <td class="text-end">
                                            @if(Auth::user()->droit === 'SupAdmin')
                                                <button class="btn btn-light btn-sm p-2" data-bs-toggle="modal" data-bs-target="#ecoleEditModal{{ $ecole->idEcole }}" title="Modifier">
                                                    <i class="bx bx-edit text-warning fs-5"></i>
                                                </button>
                                                <form action="{{ route('configuration.ecoles.destroy', $ecole->idEcole) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette école ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-light btn-sm p-2" title="Supprimer">
                                                        <i class="bx bx-trash text-danger fs-5"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-muted small">Lecture seule</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center py-4 text-muted">Aucune école trouvée.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($ecoles->hasPages())
                        <div class="mt-4">{{ $ecoles->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->droit === 'SupAdmin')
        @include('configuration.partials.ecole-modal', [
            'modalId' => 'ecoleCreateModal',
            'title' => 'Nouvelle école',
            'action' => route('configuration.ecoles.store'),
            'method' => 'POST',
            'ecole' => null,
        ])

        @foreach($ecoles as $ecole)
            @include('configuration.partials.ecole-modal', [
                'modalId' => 'ecoleEditModal'.$ecole->idEcole,
                'title' => 'Modifier une école',
                'action' => route('configuration.ecoles.update', $ecole->idEcole),
                'method' => 'PUT',
                'ecole' => $ecole,
            ])
        @endforeach
    @endif
@endsection
