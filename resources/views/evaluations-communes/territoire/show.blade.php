@extends('layouts.app')

@section('content')
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ Auth::user()->droit === 'DAE' ? 'Espace DAE' : 'Espace DCAP' }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('territoire.evaluations.index') }}">Évaluations communes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Suivi</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))<div class="alert alert-success border-0 border-start border-success border-4">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger border-0 border-start border-danger border-4">{{ $errors->first() }}</div>@endif

@php
    $total = $ecoles->count();
    $telecharge = $ecoles->whereNotNull('telecharge_at')->count();
@endphp

<div class="card theme-card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="fw-bold mb-1">{{ $evaluation->titre }}</h4>
                <div class="text-muted">{{ $evaluation->niveau_libelle ?? 'Tous niveaux' }}@if($evaluation->matiere_libelle) · {{ $evaluation->matiere_libelle }}@endif · {{ $evaluation->portee }}</div>
            </div>
            <div>@include('evaluations-communes.partials.etat')</div>
        </div>
        <div class="row g-3 mt-1 small">
            <div class="col-sm-4"><span class="text-muted">Épreuve :</span> {{ \Carbon\Carbon::parse($evaluation->debut_at)->format('d/m/Y à H:i') }} ({{ $evaluation->duree_minutes }} min)</div>
            <div class="col-sm-4"><span class="text-muted">Sujet ouvert aux écoles :</span> {{ \Carbon\Carbon::parse($evaluation->liberation_sujet_at)->format('d/m/Y à H:i') }}</div>
            <div class="col-sm-4"><span class="text-muted">Corrigé :</span> {{ $evaluation->corrige_fichier ? 'déposé, ouvert après ' . $evaluation->fin_at->format('d/m/Y H:i') : 'non déposé' }}</div>
        </div>
        @if($evaluation->consignes)<p class="mt-3 mb-0" style="white-space: pre-line;">{{ $evaluation->consignes }}</p>@endif
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a href="{{ route('territoire.evaluations.fichier', [$evaluation->id, 'sujet']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Sujet</a>
            @if($evaluation->corrige_fichier)
                <a href="{{ route('territoire.evaluations.fichier', [$evaluation->id, 'corrige']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Corrigé</a>
            @endif
            @if($gerable && $evaluation->statut === 'publiee')
                <form action="{{ route('territoire.evaluations.corrige', $evaluation->id) }}" method="POST" enctype="multipart/form-data" class="d-flex gap-2">
                    @csrf
                    <input type="file" name="corrige" class="form-control form-control-sm" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" aria-label="Fichier du corrigé">
                    <button type="submit" class="btn btn-sm btn-light text-nowrap">{{ $evaluation->corrige_fichier ? 'Remplacer le corrigé' : 'Déposer le corrigé' }}</button>
                </form>
                <form action="{{ route('territoire.evaluations.annuler', $evaluation->id) }}" method="POST" class="js-annuler-evaluation ms-auto">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Annuler l'évaluation</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="card theme-card shadow-sm">
    <div class="card-header theme-header d-flex justify-content-between flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="bi bi-check2-square me-2"></i>Réception par les écoles</h5>
        <span class="fw-semibold">{{ $telecharge }} / {{ $total }} école(s) ont téléchargé le sujet</span>
    </div>
    <div class="card-body">
        <div class="progress mb-3" style="height: 8px;" role="progressbar" aria-label="Écoles ayant téléchargé le sujet" aria-valuenow="{{ $total ? round($telecharge / $total * 100) : 0 }}" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar bg-success" style="width: {{ $total ? round($telecharge / $total * 100) : 0 }}%"></div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr><th>École</th><th>CAP</th>@if($evaluation->id_classe_officielle)<th>Classes de ce niveau</th>@endif<th>Sujet téléchargé</th></tr>
                </thead>
                <tbody>
                    @forelse($ecoles as $ecole)
                        <tr>
                            <td class="fw-semibold">{{ $ecole->nom }}</td>
                            <td>{{ $ecole->cap ?? '—' }}</td>
                            @if($evaluation->id_classe_officielle)
                                <td>{{ $ecole->a_le_niveau ? 'Oui' : 'Non renseigné' }}</td>
                            @endif
                            <td>
                                @if($ecole->telecharge_at)
                                    <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>{{ \Carbon\Carbon::parse($ecole->telecharge_at)->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="text-muted">Pas encore</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Aucune école dans cette portée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($evaluation->id_classe_officielle)
            <p class="small text-muted mt-2 mb-0">« Non renseigné » : l'école n'a pas de classe rattachée à ce niveau officiel dans KalanNet. Elle reçoit quand même le sujet.</p>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('.js-annuler-evaluation').forEach(function (formulaire) {
            formulaire.addEventListener('submit', function (event) {
                event.preventDefault();
                const titre = 'Annuler cette évaluation commune ?';
                if (!window.Swal) { if (confirm(titre)) formulaire.submit(); return; }
                Swal.fire({ title: titre, text: "Les écoles n'auront plus accès au sujet.", icon: 'warning', showCancelButton: true, confirmButtonText: "Annuler l'évaluation", cancelButtonText: 'Retour', confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d' })
                    .then(function (r) { if (r.isConfirmed) formulaire.submit(); });
            });
        });
    </script>
@endpush
@endsection
