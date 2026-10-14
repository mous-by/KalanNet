@extends('layouts.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Enseignants</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('enseignants.index') }}">Corps Enseignant</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Importer des enseignants</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>
    @endif

    @if(session('erreurs_import'))
        <div class="card border-danger shadow-sm mb-4">
            <div class="card-header bg-danger-subtle fw-bold">Lignes à corriger dans le fichier</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th style="width: 90px">Ligne</th><th>Erreur(s)</th></tr></thead>
                        <tbody>
                            @foreach(session('erreurs_import') as $ligne => $messages)
                                <tr>
                                    <td class="fw-bold">{{ $ligne }}</td>
                                    <td>
                                        <ul class="mb-0 ps-3">
                                            @foreach($messages as $message)<li>{{ $message }}</li>@endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-header theme-header">
                    <h5 class="mb-0 fw-bold">Importer des enseignants depuis Excel</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-4">
                        <li class="mb-2">Téléchargez le <strong>modèle Excel</strong> ci-dessous.</li>
                        <li class="mb-2">Remplissez une ligne par enseignant. Seuls le <strong>nom et prénom</strong> et le <strong>téléphone</strong> sont obligatoires.</li>
                        <li>Envoyez le fichier : il est entièrement vérifié avant l'import. En cas d'erreur, aucun enseignant n'est ajouté et les lignes à corriger s'affichent.</li>
                    </ol>
                    <form method="POST" action="{{ route('enseignants.import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="form-label fw-semibold" for="fichier">Fichier Excel (.xlsx, .xls)</label>
                        <input type="file" class="form-control mb-3" id="fichier" name="fichier" accept=".xlsx,.xls" required>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn px-4 theme-pill-active"><i class="bi bi-upload me-2"></i>Importer</button>
                            <a href="{{ route('enseignants.import.template') }}" class="btn btn-outline-primary px-4"><i class="bi bi-download me-2"></i>Télécharger le modèle Excel</a>
                            <a href="{{ route('enseignants.index') }}" class="btn btn-light px-4">Retour</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card theme-card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold">Colonnes du modèle</h6>
                    <ul class="small mb-3">
                        @foreach(\App\Services\Enseignants\ExcelEnseignants::COLONNES as [, $titre, $obligatoire])
                            <li>{{ $titre }}@if($obligatoire) <span class="text-danger">*</span>@endif</li>
                        @endforeach
                    </ul>
                    <p class="small text-muted mb-1">Types de contrat acceptés pour votre école : <strong>{{ implode(', ', $contrats) }}</strong>.</p>
                    <p class="small text-muted mb-0">{{ $maxLignes }} enseignants au plus par fichier. Le matricule est créé automatiquement s'il est laissé vide.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
