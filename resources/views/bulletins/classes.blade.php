@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn btn-primary rounded-circle p-2 me-3" title="Retour">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="breadcrumb-title pe-3">Bulletins</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Générer les bulletins</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-0 fw-bold">Générer les bulletins par classe</h5>
                <small class="opacity-75">Choisissez une classe, puis l'année scolaire et la période.</small>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Classe</th>
                            <th>Classe officielle</th>
                            <th>Ordre</th>
                            <th>Effectif</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $classe)
                            <tr>
                                <td class="fw-bold">{{ $classe->nom_classe }}</td>
                                <td>{{ $classe->classeOfficielle->nom_classe_officielle ?? 'Non associée' }}</td>
                                <td>{{ $classe->ordreEnseignement }}</td>
                                <td>
                                    <span class="badge bg-light text-primary border border-primary-subtle rounded-pill">
                                        {{ $classe->eleves_count }} élèves
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('pedagogie.bulletins.index', $classe->id_classe) }}" class="btn btn-primary px-4">
                                        <i class="bi bi-file-earmark-pdf me-2"></i>Générer
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Aucune classe disponible.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
