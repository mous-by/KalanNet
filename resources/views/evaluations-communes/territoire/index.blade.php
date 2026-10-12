@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ Auth::user()->droit === 'DAE' ? 'Espace DAE' : 'Espace DCAP' }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item active" aria-current="page">Évaluations communes</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))<div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>@endif

<div class="card theme-card shadow-sm">
    <div class="card-header theme-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-journal-check me-2"></i>Évaluations communes</h5>
        <button type="button" class="btn btn-sm text-white" style="background-color: var(--theme-accent);" data-bs-toggle="modal" data-bs-target="#nouvelleEvaluationModal">
            <i class="bi bi-plus-lg me-1"></i>Nouvelle évaluation
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Évaluation</th><th>Niveau · matière</th><th>Épreuve</th><th>Portée</th><th>État</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($evaluations as $evaluation)
                        <tr>
                            <td class="fw-semibold">{{ $evaluation->titre }}@if($evaluation->origine === 'academie' && Auth::user()->droit === 'DCAP')<div class="small text-muted fw-normal">Proposée par l'académie</div>@endif</td>
                            <td>{{ $evaluation->niveau_libelle ?? '—' }}<div class="small text-muted">{{ $evaluation->matiere_libelle }}</div></td>
                            <td>{{ \Carbon\Carbon::parse($evaluation->debut_at)->format('d/m/Y H:i') }}<div class="small text-muted">{{ $evaluation->duree_minutes }} min</div></td>
                            <td class="small">{{ $evaluation->portee }}</td>
                            <td>@include('evaluations-communes.partials.etat')</td>
                            <td class="text-end"><a href="{{ route('territoire.evaluations.show', $evaluation->id) }}" class="btn btn-sm btn-light">Suivi</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune évaluation commune pour l'instant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="nouvelleEvaluationModal" tabindex="-1" aria-labelledby="nouvelleEvaluationTitre" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('territoire.evaluations.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header theme-header">
                <h5 class="modal-title fw-bold" id="nouvelleEvaluationTitre"><i class="bi bi-journal-plus me-2"></i>Nouvelle évaluation commune</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold" for="ec_titre">Titre <span class="text-danger">*</span></label>
                    <input type="text" id="ec_titre" name="titre" class="form-control" maxlength="255" required value="{{ old('titre') }}" placeholder="Ex. Composition harmonisée du 1er trimestre">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="ec_niveau">Niveau</label>
                    <select id="ec_niveau" name="id_classe_officielle" class="form-select">
                        <option value="">Tous niveaux</option>
                        @foreach($niveaux as $niveau)
                            <option value="{{ $niveau->id_classe_officielle }}" @selected(old('id_classe_officielle') == $niveau->id_classe_officielle)>{{ trim($niveau->nom_classe_officielle) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="ec_matiere">Matière</label>
                    <select id="ec_matiere" name="id_matiere" class="form-select">
                        <option value="">Plusieurs matières / non précisée</option>
                        @foreach($matieres as $matiere)
                            <option value="{{ $matiere->id_matiere }}" @selected(old('id_matiere') == $matiere->id_matiere)>{{ $matiere->nom_matiere }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="ec_debut">Début de l'épreuve <span class="text-danger">*</span></label>
                    <input type="datetime-local" id="ec_debut" name="debut_at" class="form-control" required value="{{ old('debut_at') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="ec_duree">Durée (minutes) <span class="text-danger">*</span></label>
                    <input type="number" id="ec_duree" name="duree_minutes" class="form-control" min="10" max="600" required value="{{ old('duree_minutes', 120) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="ec_liberation">Sujet disponible</label>
                    <select id="ec_liberation" name="liberation_minutes" class="form-select">
                        @foreach([30 => '30 min avant', 60 => '1 h avant', 120 => '2 h avant', 1440 => 'La veille (24 h avant)', 0 => "À l'heure de début"] as $minutes => $libelle)
                            <option value="{{ $minutes }}" @selected((int) old('liberation_minutes', 60) === $minutes)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </div>
                @if($caps->isNotEmpty())
                    <div class="col-12">
                        <label class="form-label fw-semibold">Portée</label>
                        <div class="form-text mb-1">Laissez tout décoché pour envoyer à toute l'académie.</div>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach($caps as $cap)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="caps[]" value="{{ $cap->id_cap }}" id="cap{{ $cap->id_cap }}" @checked(in_array($cap->id_cap, old('caps', [])))>
                                    <label class="form-check-label" for="cap{{ $cap->id_cap }}">{{ $cap->nom_cap }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="col-12">
                    <label class="form-label fw-semibold" for="ec_consignes">Consignes aux écoles</label>
                    <textarea id="ec_consignes" name="consignes" rows="3" class="form-control" maxlength="5000">{{ old('consignes') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="ec_sujet">Sujet <span class="text-danger">*</span></label>
                    <input type="file" id="ec_sujet" name="sujet" class="form-control" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="ec_corrige">Corrigé (facultatif)</label>
                    <input type="file" id="ec_corrige" name="corrige" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <div class="col-12">
                    <div class="alert alert-info small mb-0">
                        Le sujet est confidentiel : seuls l'Admin et le Gestionnaire de chaque école pourront le télécharger, et seulement à partir de l'heure choisie. Le corrigé leur sera ouvert après la fin de l'épreuve. Une annonce prévient les Admin des écoles visées.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn text-white" style="background-color: var(--theme-accent);"><i class="bi bi-send me-1"></i>Publier</button>
            </div>
        </form>
    </div>
</div>

@if($errors->any())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById('nouvelleEvaluationModal');
                if (modal && window.bootstrap) new bootstrap.Modal(modal).show();
            });
        </script>
    @endpush
@endif
@endsection
