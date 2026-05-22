@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Programmes</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Programmes officiels</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif

    <div class="card theme-card shadow-sm mb-3">
        <div class="card-body p-4">
            <p class="mb-2 fw-bold text-muted">Filtrer les programmes officiels</p>
            <form method="GET" action="{{ route('programmes.index') }}" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Programme officiel</label>
                    <select name="id_classe_officielle" class="form-select" onchange="this.form.submit()" @disabled($classesOfficielles->isEmpty())>
                        <option value="">Tous les programmes officiels</option>
                        @foreach($classesOfficielles as $classeOfficielle)
                            <option value="{{ $classeOfficielle->id_classe_officielle }}" @selected($idClasseOfficielle == $classeOfficielle->id_classe_officielle)>
                                Programme officiel {{ $classeOfficielle->nom_classe_officielle }} - {{ $classeOfficielle->ordre_enseignement }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4" @disabled($classesOfficielles->isEmpty())>
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <a href="{{ route('programmes.index') }}" class="btn btn-light px-4">Réinitialiser</a>
                    @if($canDownloadProgrammePdf)
                        <a href="{{ route('programmes.pdf.download', ['id_classe_officielle' => $idClasseOfficielle]) }}" class="btn btn-danger px-4 @if($programmes->isEmpty()) disabled @endif">
                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                        </a>
                    @endif
                </div>
                @if($classesOfficielles->isEmpty())
                    <div class="col-12">
                        <div class="alert alert-info mb-0 border-0 border-start border-info border-4">
                            Aucun programme officiel n'est encore disponible pour le filtre.
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>

    @if($canCreateProgramme)
        <div class="text-end mb-3">
            <a href="{{ route('programmes.create') }}" class="btn px-4 theme-pill-active">
                <i class="bi bi-plus-lg me-1"></i>Nouveau programme officiel
            </a>
        </div>
    @endif

    <div class="card theme-card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3 gap-3">
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active px-4 py-2 theme-pill-active">
                            <i class="bi bi-list-task me-2"></i>Liste des programmes officiels
                        </button>
                    </li>
                </ul>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Programme officiel</th>
                            <th>Ordre</th>
                            <th>Matières</th>
                            <th>Leçons</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programmes as $idClasse => $items)
                            @php($first = $items->first())
                            <tr>
                                <td class="fw-bold">Programme officiel {{ $first->classeOfficielle->nom_classe_officielle ?? '' }}</td>
                                <td>{{ $first->classeOfficielle->ordre_enseignement ?? '' }}</td>
                                <td>{{ $items->count() }}</td>
                                <td>{{ $items->sum(fn ($item) => $item->lecons->count()) }}</td>
                                <td class="text-center">
                                    @if($canUpdateProgramme || $canDeleteProgramme)
                                        <div class="dropdown">
                                            <a class="text-muted fs-5" href="#" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                @if($canUpdateProgramme)
                                                    <li>
                                                        <a class="dropdown-item py-2" href="{{ route('programmes.edit', $first->id_programme) }}">
                                                            <i class="bi bi-pencil text-warning me-2"></i>Modifier
                                                        </a>
                                                    </li>
                                                @endif
                                                @if($canUpdateProgramme && $canDeleteProgramme)
                                                    <li><hr class="dropdown-divider"></li>
                                                @endif
                                                @if($canDeleteProgramme)
                                                    <li>
                                                        <form method="POST" action="{{ route('programmes.destroy', $first->id_programme) }}" onsubmit="return confirm('Supprimer ce programme ?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item py-2 text-danger">
                                                                <i class="bi bi-trash me-2"></i>Supprimer
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    @else
                                        <span class="text-muted">Consultation</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Aucun programme officiel enregistré.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($programmes->isNotEmpty())
        <div class="mt-4">
            @foreach($programmes as $idClasse => $items)
                @php($first = $items->first())
                <div class="card border-top border-4 border-primary shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0 fw-bold">Programme officiel {{ $first->classeOfficielle->nom_classe_officielle ?? '' }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($items as $programmeClasse)
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="fw-bold">{{ $programmeClasse->matiere->nom_matiere ?? 'Matière' }}</h6>
                                        <ol class="mb-0">
                                            @foreach($programmeClasse->lecons->sortBy('numero') as $lecon)
                                                <li>{{ $lecon->titre }}</li>
                                            @endforeach
                                        </ol>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
