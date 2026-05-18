@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Classes</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Liste des classes</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="mb-3 d-flex justify-content-end">
        <a href="{{ route('classes.create') }}" class="btn border-0 border-start border-primary border-4 bg-light-primary text-primary px-4">
            <i class="bi bi-plus-lg me-2"></i>Classe
        </a>
    </div>

    <!-- Main Card (Disposition Alliance-Team) -->
    <div class="card border-top border-4 border-primary shadow-sm mt-3">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
            @endif
            <div class="alert alert-info border-0 border-start border-info border-4" role="alert">
                <strong>Info temporaire :</strong> Cliquez sur les actions dans la colonne Action pour voir l'emploi du temps ou les détails de la classe.
            </div>
            
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered align-middle" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th>Nom de la classe</th>
                            <th>Classe officielle</th>
                            <th>Ordre Enseignement</th>
                            <th>Effectif</th>
                            <th class="text-center dt-no-sorting">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classes as $classe)
                            <tr>
                                <td class="fw-bold">{{ $classe->nom_classe }}</td>
                                <td>{{ $classe->classeOfficielle->nom_classe_officielle ?? 'Non associée' }}</td>
                                <td>{{ $classe->ordreEnseignement }}</td>
                                <td><span class="badge bg-light text-primary border border-primary-subtle rounded-pill">{{ $classe->eleves_count }} élèves</span></td>
                                <td class="text-center">
                                    <div class="dropdown">
                                        <a class="text-muted fs-5" href="#" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('classes.show', $classe->id_classe) }}">
                                                    <i class="bi bi-eye text-info me-2"></i>Aperçu
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('classes.edit', $classe->id_classe) }}">
                                                    <i class="bi bi-pencil text-warning me-2"></i>Modifier
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('pedagogie.timetable', ['id_classe' => $classe->id_classe]) }}">
                                                    <i class="bi bi-calendar-plus text-primary me-2"></i>Emploi du temps
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('pedagogie.bulletins.index', $classe->id_classe) }}">
                                                    <i class="bi bi-card-list text-primary me-2"></i>Bulletins
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('classes.destroy', $classe->id_classe) }}" method="POST" onsubmit="return confirm('Supprimer cette classe ?');">
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
                                <td colspan="5" class="text-center py-4 text-muted">Aucune classe n'a encore été créée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .bg-light-primary { background-color: rgba(var(--bs-primary-rgb), 0.1) !important; }
    </style>
@endsection
