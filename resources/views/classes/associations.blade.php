@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Classes</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('classes.index') }}">Liste des classes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Associer les classes</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    <div class="card border-top border-4 border-primary shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold">Associer les classes de l'école aux classes officielles</h5>
            <a href="{{ route('configuration.classes-officielles') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-building-check me-1"></i>Référentiel officiel
            </a>
        </div>
        <div class="card-body">
            @if(auth()->user()->droit === 'SupAdmin')
                <form method="GET" action="{{ route('classes.associations') }}" class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label">École</label>
                        <select name="id_ecole" class="form-select" onchange="this.form.submit()">
                            @foreach($ecoles as $ecole)
                                <option value="{{ $ecole->idEcole }}" @selected((int) $idEcole === (int) $ecole->idEcole)>{{ $ecole->nomEcole }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            @endif

            <form method="POST" action="{{ route('classes.associations.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="id_ecole" value="{{ $idEcole }}">

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Classe créée dans l'école</th>
                                <th>Ordre</th>
                                <th>Classe officielle correspondante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($classes as $classe)
                                <tr>
                                    <td class="fw-bold">{{ $classe->nom_classe }}</td>
                                    <td>{{ $classe->ordreEnseignement }}</td>
                                    <td>
                                        <select name="associations[{{ $classe->id_classe }}]" class="form-select">
                                            <option value="">Non associée</option>
                                            @foreach($classesOfficielles as $officielle)
                                                <option value="{{ $officielle->id_classe_officielle }}"
                                                        data-ordre="{{ $officielle->ordre_enseignement }}"
                                                        @selected((int) $classe->id_classe_officielle === (int) $officielle->id_classe_officielle)>
                                                    {{ $officielle->nom_classe_officielle }} - {{ $officielle->ordre_enseignement }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Aucune classe trouvée pour cette école.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('classes.index') }}" class="btn btn-light px-4">Retour</a>
                    <button type="submit" class="btn btn-primary px-4">Enregistrer les associations</button>
                </div>
            </form>
        </div>
    </div>
@endsection
